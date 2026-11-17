<?php

// Event-boundary regression plus fail-closed reentrancy; MySQL process test complements this gate.
use App\Events\InquiryMessageCreated;
use App\Jobs\BroadcastInquiryMessage;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\Account\UpdateUserStatus;
use App\Services\Inquiry\InquiryLock;
use App\Services\Inquiry\InquiryService;
use App\Services\Inquiry\MessageService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

require dirname(__DIR__, 2).'/vendor/autoload.php';
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:',
    'DB_URL' => '', 'DB_QUEUE_CONNECTION' => 'sqlite', 'BROADCAST_CONNECTION' => 'null', 'CACHE_STORE' => 'array'] as $key => $value) {
    $_ENV[$key] = $_SERVER[$key] = $value;
    putenv("$key=$value");
}
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $e): void {
    fwrite(STDERR, 'FAIL publication boundary regression: '.$e::class.PHP_EOL);
    exit(1);
});
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Refusing publication boundary regression outside SQLite memory.');
}
Artisan::call('migrate', ['--force' => true]);
$customer = User::factory()->create();
$sales = User::factory()->create();
$sales->forceFill(['role' => 'sales'])->save();
$replacement = User::factory()->create();
$replacement->forceFill(['role' => 'sales'])->save();
$admin = User::factory()->create();
$admin->forceFill(['role' => 'admin'])->save();
$interleave = null;
$blocked = false;
DB::listen(function ($query) use (&$interleave, &$blocked): void {
    if ($interleave && str_starts_with(strtolower($query->sql), 'select exists(')
        && str_contains($query->sql, 'inquiries') && str_contains($query->sql, 'access_version')) {
        $action = $interleave;
        $interleave = null;
        try {
            $action(); // Same original race injection point; nested mutation must fail closed.
        } catch (LogicException $e) {
            if (! str_contains($e->getMessage(), 'Publication gate')) {
                throw $e;
            }
            $blocked = true;
        }
    }
});
$failures = 0;
foreach (['reassign', 'H02-lock'] as $scenario) {
    $blocked = false;
    $inquiry = Inquiry::create(['customer_id' => $customer->id, 'assigned_to' => $sales->id,
        'contact_name' => 'Security regression fixture', 'contact_phone' => '0900000000',
        'content' => 'SQLite memory fixture', 'status' => 'in_progress']);
    [$message] = app(MessageService::class)->send($customer, $inquiry, [
        'content' => 'Security boundary fixture', 'client_message_id' => (string) Str::uuid(),
    ]);
    Event::fake([InquiryMessageCreated::class]);
    $revoke = function () use ($scenario, $admin, $sales, $replacement, $inquiry): void {
        if ($scenario === 'reassign') {
            app(InquiryService::class)->update($admin, $inquiry, 'assign', ['assigned_to' => $replacement->id]);
        } else {
            app(UpdateUserStatus::class)->handle($admin, $sales, false);
        }
        if (DB::transactionLevel() !== 0 || $inquiry->fresh()->access_version !== 2) {
            throw new RuntimeException('Revocation must have committed before dispatch.');
        }
    };
    $interleave = $revoke;
    (new BroadcastInquiryMessage($message->id, 1))->handle(app(InquiryLock::class));
    if ($interleave !== null) {
        throw new RuntimeException('Race injection point was not reached; update the regression for the new design.');
    }
    if (! $blocked || $inquiry->fresh()->access_version !== 1) {
        $failures++;
        echo "FAIL F01 $scenario: revocation crossed the protected publication boundary.\n";
    } else {
        Event::assertDispatched(InquiryMessageCreated::class, 1);
        $revoke(); // A separate operation may now commit after publication returns.
        (new BroadcastInquiryMessage($message->id, 1))->handle(app(InquiryLock::class));
        Event::assertDispatched(InquiryMessageCreated::class, 1);
        echo "PASS F01 $scenario: original injection refused; revocation commits after publication; stale job suppressed.\n";
    }
}
exit($failures ? 1 : 0);
