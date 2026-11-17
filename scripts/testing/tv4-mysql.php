<?php

// Opt-in integration harness. Never loads the demo database for migrations or fixtures.
use App\Enums\UserRole;
use App\Models\Inquiry;
use App\Models\Message;
use App\Models\User;
use App\Services\Inquiry\InquiryService;
use App\Services\Inquiry\MessageService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$root = dirname(__DIR__, 2);
if (! is_file($root.'/.env.tv4-testing')) {
    throw new RuntimeException('Create a private .env.tv4-testing pointing to a dedicated local MySQL test database.');
}
Dotenv\Dotenv::createImmutable($root, '.env.tv4-testing')->load();
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.mysql.database');
if (config('database.default') !== 'mysql' || config('database.connections.mysql.host') !== '127.0.0.1'
    || ! preg_match('/^toyota_showroom_tv4_test_\d+$/', $database) || ! app()->environment('testing')) {
    throw new RuntimeException('Refusing integration work outside dedicated local TV4 test database.');
}
$mode = $argv[1] ?? 'run';
if (in_array($mode, ['crash-before-commit', 'crash-after-commit'], true)) {
    config(['queue.connections.database.queue' => 'atomic-'.$argv[4]]);
    if ($mode === 'crash-before-commit') {
        DB::beginTransaction();
    }
    app(MessageService::class)->send(User::findOrFail($argv[2]), Inquiry::findOrFail($argv[3]), [
        'content' => 'Atomic crash fixture', 'client_message_id' => $argv[4],
    ]);
    echo "READY\n";
    flush();
    sleep(30); // Parent terminates this process at the selected transaction boundary.
    throw new RuntimeException('Parent did not terminate crash fixture');
}
if ($mode === 'send') {
    while (microtime(true) < (float) $argv[5]) {
        usleep(1000);
    }
    config(['queue.default' => 'database']);
    [$message, $created] = app(MessageService::class)->send(User::findOrFail($argv[2]), Inquiry::findOrFail($argv[3]), [
        'content' => 'Concurrent UUID test', 'client_message_id' => $argv[4],
    ]);
    echo json_encode(['id' => $message->id, 'created' => $created]);
    exit;
}
if ($mode === 'assign') {
    while (microtime(true) < (float) $argv[5]) {
        usleep(1000);
    }
    app(InquiryService::class)->update(User::findOrFail($argv[2]), Inquiry::findOrFail($argv[3]), 'assign', ['assigned_to' => (int) $argv[4]]);
    echo 'assigned';
    exit;
}
if ($mode !== 'run') {
    throw new RuntimeException('Unknown test mode');
}
Artisan::call('migrate', ['--force' => true]);
$token = Str::lower(Str::random(10));
$password = bin2hex(random_bytes(16));
$users = [];
foreach (['customer', 'sales', 'replacement', 'admin'] as $name) {
    $user = User::create(['name' => 'TV4 '.$name, 'email' => "$name.$token@example.test", 'phone' => '0900000000', 'password' => $password]);
    $user->forceFill(['role' => match ($name) {
        'sales', 'replacement' => UserRole::Sales,
        'admin' => UserRole::Admin,
        default => UserRole::Customer,
    }])->save();
    $users[$name] = $user;
}
$inquiry = Inquiry::create([
    'customer_id' => $users['customer']->id, 'assigned_to' => $users['sales']->id,
    'contact_name' => 'Integration demo', 'contact_phone' => '0900000000',
    'content' => 'MySQL isolated integration test', 'status' => 'assigned',
]);
$uuid = (string) Str::uuid();
$start = (string) (microtime(true) + 1.5);
$commands = [];
foreach ([1, 2] as $n) {
    $process = new Process([PHP_BINARY, __FILE__, 'send', (string) $users['customer']->id, (string) $inquiry->id, $uuid, $start], $root);
    $process->start();
    $commands[] = $process;
}
$results = [];
foreach ($commands as $process) {
    $process->wait();
    if (! $process->isSuccessful()) {
        throw new RuntimeException('Concurrent send process failed: '.$process->getErrorOutput());
    }
    $results[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
}
if ($results[0]['id'] !== $results[1]['id'] || count(array_filter($results, fn ($r) => $r['created'])) !== 1
    || Message::where('sender_id', $users['customer']->id)->where('client_message_id', $uuid)->count() !== 1) {
    throw new RuntimeException('Concurrent UUID invariant failed');
}
echo "PASS MySQL: concurrent processes returned one persisted message and one creation.\n";
// Reassignment and send start together using independent PHP/PDO connections.
$start = (string) (microtime(true) + 1.5);
$send = new Process([PHP_BINARY, __FILE__, 'send', (string) $users['customer']->id, (string) $inquiry->id, (string) Str::uuid(), $start], $root);
$assign = new Process([PHP_BINARY, __FILE__, 'assign', (string) $users['admin']->id, (string) $inquiry->id, (string) $users['replacement']->id, $start], $root);
$send->start();
$assign->start();
$send->wait();
$assign->wait();
if (! $send->isSuccessful() || ! $assign->isSuccessful() || $inquiry->fresh()->assigned_to !== $users['replacement']->id
    || $inquiry->fresh()->access_version !== 2) {
    throw new RuntimeException('Concurrent assignment/send failed');
}
echo "PASS MySQL: send/reassignment serialize without deadlock; access_version=2.\n";
foreach (['crash-before-commit', 'crash-after-commit'] as $boundary) {
    $crashUuid = (string) Str::uuid();
    $child = new Process([PHP_BINARY, __FILE__, $boundary, (string) $users['customer']->id, (string) $inquiry->id, $crashUuid], $root);
    $child->start();
    try {
        $deadline = microtime(true) + 10;
        while (! str_contains($child->getOutput(), 'READY')) {
            if (! $child->isRunning() || microtime(true) > $deadline) {
                throw new RuntimeException('Crash fixture did not reach selected boundary');
            }
            usleep(10000);
        }
        $beforeCommit = $boundary === 'crash-before-commit';
        $count = Message::where('client_message_id', $crashUuid)->count();
        $intentCount = DB::table('jobs')->where('queue', 'atomic-'.$crashUuid)->count();
        if ($count !== ($beforeCommit ? 0 : 1) || $intentCount !== ($beforeCommit ? 0 : 1)) {
            throw new RuntimeException('Independent connection observed non-atomic message/intent');
        }
        if ($beforeCommit && app('queue')->connection('database')->pop('atomic-'.$crashUuid) !== null) {
            throw new RuntimeException('Worker observed uncommitted intent');
        }
    } finally {
        $child->stop(0.1);
    }
    $intentBeforeRetry = DB::table('jobs')->count();
    [$persisted, $created] = app(MessageService::class)->send($users['customer'], $inquiry->fresh(), [
        'content' => 'Atomic crash fixture', 'client_message_id' => $crashUuid,
    ]);
    if ($created !== $beforeCommit || Message::where('client_message_id', $crashUuid)->count() !== 1
        || DB::table('jobs')->count() !== $intentBeforeRetry + ($beforeCommit ? 1 : 0)
        || DB::table('jobs')->where('queue', 'atomic-'.$crashUuid)->count() !== ($beforeCommit ? 0 : 1)) {
        throw new RuntimeException('Crash recovery/UUID invariant failed');
    }
    echo "PASS MySQL: $boundary, independent visibility and UUID recovery preserve one message/intent.\n";
}
$fixture = [
    'inquiry_id' => $inquiry->id, 'password' => $password,
    'users' => array_map(fn ($user) => ['id' => $user->id, 'email' => $user->email], $users),
    'database' => $database,
];
$path = storage_path('app/tv4-integration-fixture.json');
file_put_contents($path, json_encode($fixture, JSON_THROW_ON_ERROR));
echo "Test fixtures retained in dedicated test DB; private fixture saved under ignored storage/app.\n";
