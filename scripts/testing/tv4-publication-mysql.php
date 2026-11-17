<?php

use App\Events\InquiryMessageCreated;
use App\Jobs\BroadcastInquiryMessage;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\Account\UpdateUserStatus;
use App\Services\Inquiry\InquiryLock;
use App\Services\Inquiry\InquiryService;
use App\Services\Inquiry\MessageService;
use App\Services\Inquiry\PublicationGate;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
Dotenv\Dotenv::createImmutable($root, '.env.tv4-testing')->load();
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $e): void {
    fwrite(STDERR, 'FAIL publication regression: '.$e::class.PHP_EOL);
    exit(1);
});
if (! app()->environment('testing') || config('database.default') !== 'mysql'
    || config('database.connections.mysql.host') !== '127.0.0.1'
    || ! preg_match('/^toyota_showroom_tv4_test_\d+$/', DB::connection()->getDatabaseName())) {
    throw new RuntimeException('Dedicated local MySQL test database required.');
}
$mode = $argv[1] ?? 'run';
if ($mode === 'hold') {
    app(PublicationGate::class)->run(function (): void {
        echo "READY\n";
        flush();
        sleep(30);
    });
    exit;
}
if ($mode === 'revoke') {
    echo "START\n";
    flush();
    $admin = User::findOrFail($argv[3]);
    $inquiry = Inquiry::findOrFail($argv[4]);
    if ($argv[2] === 'reassign') {
        app(InquiryService::class)->update($admin, $inquiry, 'assign', ['assigned_to' => (int) $argv[5]]);
    } else {
        app(UpdateUserStatus::class)->handle($admin, User::findOrFail($argv[5]), false);
    }
    echo "COMMITTED\n";
    exit;
}
if ($mode === 'fixture') {
    $inquiry = app(InquiryService::class)->create(User::findOrFail($argv[2]), [
        'name' => 'Publication integration fixture', 'phone' => '0900000000', 'content' => 'Isolated publication fixture',
    ]);
    app(InquiryService::class)->update(User::findOrFail($argv[4]), $inquiry, 'assign', ['assigned_to' => (int) $argv[3]]);
    echo json_encode(['id' => $inquiry->id, 'version' => $inquiry->fresh()->access_version]);
    exit;
}
if ($mode === 'publish') {
    $inquiry = Inquiry::findOrFail($argv[3]);
    [$message] = app(MessageService::class)->send(User::findOrFail($argv[2]), $inquiry, [
        'content' => 'Fenced live publication', 'client_message_id' => (string) Str::uuid(),
    ]);
    $armed = true;
    DB::listen(function ($query) use (&$armed, $message): void {
        if ($armed && str_starts_with(strtolower($query->sql), 'select exists(')
            && str_contains($query->sql, 'access_version')) {
            $armed = false;
            if (DB::transactionLevel() !== 0) {
                throw new RuntimeException('Network boundary retains a transaction');
            }
            echo json_encode(['phase' => 'BOUNDARY', 'message_id' => $message->id])."\n";
            flush();
            if (trim(fgets(STDIN) ?: '') !== 'PUBLISH') {
                throw new RuntimeException('Missing publication release barrier');
            }
        }
    });
    (new BroadcastInquiryMessage($message->id, $inquiry->access_version))->handle(app(InquiryLock::class));
    if ($armed) {
        throw new RuntimeException('Publication boundary not reached');
    }
    echo "PUBLISHED\n";
    exit;
}
if ($mode !== 'run') {
    throw new RuntimeException('Unknown mode');
}
// Schema must already exist from tv4-mysql.php; no reset/migration here.
$admin = User::factory()->create();
$admin->forceFill(['role' => 'admin'])->save();
$next = User::factory()->create();
$next->forceFill(['role' => 'sales'])->save();
foreach (['reassign', 'lock-sales', 'lock-customer'] as $scenario) {
    $customer = User::factory()->create();
    $sales = User::factory()->create();
    $sales->forceFill(['role' => 'sales'])->save();
    $i = app(InquiryService::class)->create($customer, ['name' => 'MySQL fence fixture', 'phone' => '0900000000', 'content' => 'MySQL fence regression fixture']);
    app(InquiryService::class)->update($admin, $i, 'assign', ['assigned_to' => $sales->id]);
    $i->refresh();
    [$message] = app(MessageService::class)->send($customer, $i, ['content' => 'Publication fence fixture', 'client_message_id' => (string) Str::uuid()]);
    $target = match ($scenario) {
        'reassign' => $next->id, 'lock-sales' => $sales->id, default => $customer->id
    };
    $revoker = new Process([PHP_BINARY, __FILE__, 'revoke', $scenario, (string) $admin->id, (string) $i->id, (string) $target], $root);
    $revoker->setTimeout(15);
    $state = (object) ['armed' => true];
    DB::listen(function ($query) use ($state, $revoker, $i): void {
        if ($state->armed && str_starts_with(strtolower($query->sql), 'select exists(') && str_contains($query->sql, 'access_version')) {
            $state->armed = false;
            $revoker->start();
            $deadline = microtime(true) + 5;
            while (! str_contains($revoker->getOutput(), 'START')) {
                if (! $revoker->isRunning() || microtime(true) > $deadline) {
                    throw new RuntimeException('Revoker did not start');
                }
                usleep(10000);
            }
            usleep(250000);
            if (! $revoker->isRunning() || str_contains($revoker->getOutput(), 'COMMITTED')
                || $i->fresh()->access_version !== 2 || DB::transactionLevel() !== 0) {
                throw new RuntimeException('Revocation crossed active publication gate');
            }
        }
    });
    Event::fake([InquiryMessageCreated::class]);
    try {
        (new BroadcastInquiryMessage($message->id, 2))->handle(app(InquiryLock::class));
        $revoker->wait();
        if ($state->armed || ! $revoker->isSuccessful() || $i->fresh()->access_version !== 3) {
            throw new RuntimeException('Revocation did not commit after publication');
        }
        Event::assertDispatched(InquiryMessageCreated::class, 1);
        (new BroadcastInquiryMessage($message->id, 2))->handle(app(InquiryLock::class));
        Event::assertDispatched(InquiryMessageCreated::class, 1);
    } finally {
        if ($revoker->isRunning()) {
            $revoker->stop(0.1);
        }
    }
    echo "PASS MySQL $scenario: revoker blocked at original SELECT/dispatch boundary; stale job after commit suppressed.\n";
}
$holder = new Process([PHP_BINARY, __FILE__, 'hold'], $root);
$holder->start();
try {
    $deadline = microtime(true) + 5;
    while (! str_contains($holder->getOutput(), 'READY')) {
        if (! $holder->isRunning() || microtime(true) > $deadline) {
            throw new RuntimeException('Gate holder did not start');
        }
        usleep(10000);
    }
} finally {
    $holder->stop(0.1);
}
app(PublicationGate::class)->run(fn () => print ("PASS MySQL: process death releases session gate; no expiring lease.\n"));

$customer = User::factory()->create();
$i = app(InquiryService::class)->create($customer, ['name' => 'Gate loss fixture', 'phone' => '0900000000', 'content' => 'Isolated gate ownership regression']);
app(InquiryService::class)->update($admin, $i, 'assign', ['assigned_to' => $next->id]);
$i->refresh();
[$message] = app(MessageService::class)->send($customer, $i, ['content' => 'Gate loss fixture', 'client_message_id' => (string) Str::uuid()]);
$loss = (object) ['armed' => true];
DB::listen(function ($query) use ($loss): void {
    if ($loss->armed && str_starts_with(strtolower($query->sql), 'select exists(') && str_contains($query->sql, 'access_version')) {
        $loss->armed = false;
        $name = 'toyota:publication:'.substr(hash('sha256', DB::connection()->getDatabaseName()), 0, 40);
        $owner = (int) DB::selectOne('SELECT IS_USED_LOCK(?) AS owner', [$name])->owner;
        $primary = (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
        if ($owner < 1 || $owner === $primary) {
            throw new RuntimeException('Expected dedicated gate session owned by this publisher');
        }
        // Guarded TEST DB only: terminate this publisher's dedicated session, never a demo session.
        DB::statement('KILL CONNECTION '.$owner);
    }
});
Event::fake([InquiryMessageCreated::class]);
try {
    (new BroadcastInquiryMessage($message->id, 2))->handle(app(InquiryLock::class));
    throw new RuntimeException('Lost gate ownership must fail closed before dispatch');
} catch (PDOException $e) {
    if (! in_array($e->errorInfo[1] ?? null, [2006, 2013], true)) {
        throw $e;
    }
}
Event::assertNotDispatched(InquiryMessageCreated::class);
if ($loss->armed || ! $message->fresh()) {
    throw new RuntimeException('Gate loss fixture did not preserve durable message');
}
app(UpdateUserStatus::class)->handle($admin, $customer, false);
echo "PASS MySQL: dedicated session loss detected before dispatch; message retained; gate state released.\n";
