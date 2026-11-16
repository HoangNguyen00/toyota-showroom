<?php

// Independent processes; never run against the shared/demo database.
use App\Models\Car;
use App\Models\CarCategory;
use App\Services\Catalog\StoreCarImage;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
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
    throw new RuntimeException('Dedicated local MySQL test database required');
}
if (($argv[1] ?? '') === 'upload') {
    while (microtime(true) < (float) $argv[3]) {
        usleep(1000);
    }
    try {
        app(StoreCarImage::class)->handle(Car::findOrFail($argv[2]), UploadedFile::fake()->image('concurrent.jpg', 800, 450), true);
        echo 'CREATED';
    } catch (ValidationException $e) {
        if (! isset($e->errors()['image'])) {
            throw $e;
        }
        echo 'LIMIT';
    }
    exit;
}
$category = CarCategory::create(['brand' => 'Toyota', 'name' => 'Image race QA', 'slug' => 'image-race-'.Str::lower(Str::random(10)), 'is_visible' => true]);
$car = Car::create(['category_id' => $category->id, 'code' => 'RACE-'.Str::upper(Str::random(10)), 'slug' => 'race-'.Str::lower(Str::random(10)),
    'name' => 'Image race QA', 'year' => 2026, 'color' => 'Demo', 'price' => 600000000, 'is_visible' => false]);
foreach (range(1, 9) as $index) {
    $car->images()->create(['path' => 'assets/qa-'.$index.'.jpg', 'sort_order' => $index, 'is_primary' => $index === 1]);
}
$start = (string) (microtime(true) + 1.5);
$processes = [];
foreach ([1, 2] as $index) {
    $process = new Process([PHP_BINARY, __FILE__, 'upload', (string) $car->id, $start], $root);
    $process->setTimeout(20);
    $process->start();
    $processes[] = $process;
}
$results = [];
foreach ($processes as $process) {
    $process->wait();
    if (! $process->isSuccessful()) {
        throw new RuntimeException('Image process failed: '.$process->getErrorOutput());
    }
    $results[] = trim($process->getOutput());
}
sort($results);
if ($results !== ['CREATED', 'LIMIT'] || $car->images()->count() !== 10 || $car->images()->where('is_primary', true)->count() !== 1) {
    throw new RuntimeException('Concurrent upload broke the image count/primary invariant');
}
echo "PASS MySQL image race: one upload accepted, one rejected; 10 images and one primary.\n";
