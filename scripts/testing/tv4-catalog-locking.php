<?php

// Separate local MySQL fixture only; no Catalog code or demo data is changed.
use App\Models\Car;
use App\Models\CarCategory;
use App\Services\Inquiry\InquiryService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
Dotenv\Dotenv::createImmutable($root, '.env.tv4-testing')->load();
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || config('database.default') !== 'mysql'
    || config('database.connections.mysql.host') !== '127.0.0.1'
    || ! preg_match('/^toyota_showroom_tv4_test_\d+$/', DB::connection()->getDatabaseName())) {
    throw new RuntimeException('Refusing catalog locking regression outside dedicated local MySQL test DB.');
}
if (($argv[1] ?? '') === 'mutate') {
    DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
    $scenario = $argv[2];
    $table = $scenario === 'category-hidden' ? 'car_categories' : 'cars';
    $fields = $scenario === 'car-sold' ? ['status' => 'sold'] : ['is_visible' => false];
    try {
        DB::table($table)->where('id', (int) $argv[3])->update($fields);
        echo 'UPDATED';
    } catch (QueryException $e) {
        if (($e->errorInfo[1] ?? null) !== 1205) {
            throw $e;
        }
        echo 'BLOCKED';
    }
    exit;
}
Artisan::call('migrate', ['--force' => true]);
$armed = false;
$competitor = null;
DB::listen(function ($query) use (&$armed, &$competitor): void {
    if ($armed && preg_match('/^select \* from [`"]cars[`"]/', $query->sql)) {
        $armed = false;
        $competitor->run();
        if (! $competitor->isSuccessful() || $competitor->getOutput() !== 'BLOCKED') {
            throw new RuntimeException('Catalog mutation committed between validation read and inquiry INSERT');
        }
    }
});
foreach (['car-sold', 'car-hidden', 'category-hidden'] as $scenario) {
    $token = Str::lower(Str::random(12));
    $category = CarCategory::create(['brand' => 'Toyota', 'name' => 'TV4 isolated fixture', 'slug' => $token, 'is_visible' => true]);
    $car = Car::create(['category_id' => $category->id, 'code' => $token, 'slug' => $token,
        'name' => 'TV4 isolated fixture', 'year' => 2026, 'color' => 'Demo', 'price' => '1000000000',
        'is_visible' => true, 'status' => 'available']);
    $target = $scenario === 'category-hidden' ? $category->id : $car->id;
    $competitor = new Process([PHP_BINARY, __FILE__, 'mutate', $scenario, (string) $target], $root);
    $competitor->setTimeout(8);
    $armed = true;
    $data = ['name' => 'TV4 isolated fixture', 'phone' => '0900000000', 'content' => 'Catalog race regression fixture', 'car_id' => $car->id];
    app(InquiryService::class)->create(null, $data);
    if ($armed) {
        throw new RuntimeException('Locking read injection was not reached');
    }
    $competitor->run();
    if (! $competitor->isSuccessful() || $competitor->getOutput() !== 'UPDATED') {
        throw new RuntimeException('Catalog mutation could not complete after inquiry commit');
    }
    try {
        app(InquiryService::class)->create(null, $data);
        throw new RuntimeException('Inquiry accepted a committed sold/hidden target');
    } catch (ValidationException $e) {
        if (! isset($e->errors()['car_id'])) {
            throw $e;
        }
    }
    echo "PASS MySQL $scenario: concurrent UPDATE blocked until inquiry commit; subsequent creation rejected.\n";
}
