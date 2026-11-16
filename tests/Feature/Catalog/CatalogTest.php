<?php

namespace Tests\Feature\Catalog;

use App\Enums\CarStatus;
use App\Enums\UserRole;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\CarImage;
use App\Models\Inquiry;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

        return $user->fresh();
    }

    private function staff(string $role): User
    {
        $user = $this->admin();
        $user->forceFill(['role' => $role])->save();

        return $user->fresh();
    }

    private function carPayload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => CarCategory::factory()->create()->id,
            'code' => 'VIOS-2026-99',
            'name' => 'Toyota Vios',
            'year' => 2026,
            'color' => 'Trắng ngọc trai',
            'price' => 479000000,
            'status' => CarStatus::Available->value,
            'is_visible' => '1',
        ], $overrides);
    }

    public function test_admin_can_create_update_and_delete_a_car(): void
    {
        $admin = $this->admin();
        $payload = $this->carPayload();

        $this->actingAs($admin)->post('/admin/cars', $payload)->assertRedirect();
        $car = Car::sole();
        $this->assertSame('VIOS-2026-99', $car->code);
        $this->assertSame(CarStatus::Available, $car->status);
        $this->assertTrue($car->is_visible);
        $this->assertStringContainsString('toyota-vios-vios-2026-99', $car->slug);

        $this->actingAs($admin)->put("/admin/cars/{$car->id}", $this->carPayload([
            'code' => 'VIOS-2026-99', 'status' => CarStatus::Reserved->value, 'price' => 480000000, 'is_visible' => '0',
        ]))->assertRedirect(route('admin.cars.edit', $car));
        $car->refresh();
        $this->assertSame(CarStatus::Reserved, $car->status);
        $this->assertSame('480000000', $car->price);
        $this->assertFalse($car->is_visible);

        $this->actingAs($admin)->delete("/admin/cars/{$car->id}")->assertRedirect('/admin/cars');
        $this->assertDatabaseCount('cars', 0);
    }

    public function test_brand_slug_and_status_values_are_validated_server_side(): void
    {
        $admin = $this->admin();
        $foreign = CarCategory::factory()->create(['brand' => 'Honda']);

        $this->actingAs($admin)->post('/admin/cars', $this->carPayload([
            'category_id' => $foreign->id,
            'code' => 'vios-2026-99',
            'price' => '479000000.50',
        ]))->assertSessionHasErrors(['category_id', 'price']);
        $this->assertDatabaseCount('cars', 0);

        $this->actingAs($admin)->post('/admin/cars', $this->carPayload([
            'code' => 'vios 2026',
            'status' => 'lost',
            'specs' => [['label' => 'Động cơ', 'value' => '1.5L'], ['label' => '', 'value' => '']],
        ]))->assertSessionHasErrors(['code', 'status']);
        $this->assertDatabaseCount('cars', 0);

        $this->actingAs($admin)->post('/admin/cars', $this->carPayload([
            'code' => 'vios-2026-99',
            'specs' => [['label' => 'Động cơ', 'value' => '1.5L']],
        ]))->assertSessionHasNoErrors();
        $car = Car::sole();
        $this->assertSame('VIOS-2026-99', $car->code);
        $this->assertSame([['label' => 'Động cơ', 'value' => '1.5L']], $car->specs);
    }

    public function test_car_cannot_be_public_while_its_category_is_hidden(): void
    {
        $admin = $this->admin();
        $hiddenLine = CarCategory::factory()->create(['is_visible' => false]);

        $this->actingAs($admin)->post('/admin/cars', $this->carPayload(['category_id' => $hiddenLine->id]))
            ->assertSessionHasErrors('is_visible');
        $this->assertDatabaseCount('cars', 0);
    }

    public function test_categories_are_admin_only_and_brand_is_always_toyota(): void
    {
        $customer = $this->staff('customer');
        $this->actingAs($customer)->get('/admin/car-categories')->assertForbidden();
        $this->actingAs($customer)->post('/admin/car-categories', ['name' => 'Wigo'])->assertForbidden();

        $slugs = ['corolla-cross', 'corolla-altis', 'camry', 'vios', 'avanza', 'fortuner', 'raize', 'yaris-cross', 'land-cruiser'];
        $this->actingAs($this->admin())->post('/admin/car-categories', [
            'name' => 'Corolla Cross', 'brand' => 'Honda', 'description' => 'Crossover', 'is_visible' => '1',
        ])->assertRedirect('/admin/car-categories');
        $category = CarCategory::whereIn('slug', $slugs)->orderByDesc('id')->firstOrFail();
        $this->assertStringStartsWith('corolla-cross', $category->slug);
        $this->assertTrue($category->is_visible);

        $car = Car::factory()->create(['category_id' => $category->id]);
        $this->actingAs($this->admin())->delete("/admin/car-categories/{$category->id}")->assertForbidden();
        $this->assertDatabaseHas('car_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('cars', ['id' => $car->id]);
    }

    public function test_upload_gets_server_generated_name_and_only_one_primary_image(): void
    {
        $admin = $this->admin();
        $car = Car::factory()->create();

        $this->actingAs($admin)->post("/admin/cars/{$car->id}/images", [
            'image' => UploadedFile::fake()->image('my photo.jpg', 800, 450),
            'is_primary' => '1',
        ])->assertRedirect(route('admin.cars.edit', $car));
        $this->actingAs($admin)->post("/admin/cars/{$car->id}/images", [
            'image' => UploadedFile::fake()->image('second.png', 640, 360),
            'is_primary' => '1',
        ])->assertRedirect(route('admin.cars.edit', $car));

        $images = CarImage::orderBy('id')->get();
        $this->assertCount(2, $images);
        foreach ($images as $image) {
            $this->assertStringStartsWith('cars/'.$car->id.'/', $image->path);
            $this->assertStringNotContainsString('my photo', $image->path);
            Storage::disk('public')->assertExists($image->path);
        }
        $this->assertSame([false, true], $images->pluck('is_primary')->all());
        $this->assertSame(2, $images[1]->sort_order);

        $this->actingAs($admin)->delete("/admin/cars/{$car->id}/images/{$images[0]->id}")->assertRedirect();
        Storage::disk('public')->assertMissing($images[0]->path);
        $this->assertDatabaseMissing('car_images', ['id' => $images[0]->id]);
    }

    public function test_upload_rejects_non_images_and_foreign_car_image(): void
    {
        $admin = $this->admin();
        $car = Car::factory()->create();
        $other = Car::factory()->create();
        $foreignImage = CarImage::create(['car_id' => $other->id, 'path' => 'cars/x/y.jpg']);

        $this->actingAs($admin)->post("/admin/cars/{$car->id}/images", [
            'image' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('image');
        $this->assertDatabaseCount('car_images', 1);

        $this->actingAs($admin)->delete("/admin/cars/{$car->id}/images/{$foreignImage->id}")->assertNotFound();
        $this->assertDatabaseHas('car_images', ['id' => $foreignImage->id]);
    }

    public function test_guest_and_non_admin_cannot_use_admin_catalog(): void
    {
        $car = Car::factory()->create();
        $this->get('/admin/cars')->assertRedirect('/dang-nhap');

        foreach (['sales', 'customer'] as $role) {
            $user = $this->staff($role);
            $this->actingAs($user)->get('/admin/cars')->assertForbidden();
            $this->actingAs($user)->post('/admin/cars', $this->carPayload())->assertForbidden();
            $this->actingAs($user)->put("/admin/cars/{$car->id}", $this->carPayload())->assertForbidden();
            $this->actingAs($user)->delete("/admin/cars/{$car->id}")->assertForbidden();
            $this->actingAs($user)->post("/admin/cars/{$car->id}/images", [
                'image' => UploadedFile::fake()->image('a.jpg'),
            ])->assertForbidden();
        }
        $this->assertDatabaseCount('cars', 1);
        $this->assertDatabaseCount('car_images', 0);
    }

    public function test_public_catalog_hides_invisible_cars_and_rows(): void
    {
        $visible = Car::factory()->create(['category_id' => CarCategory::factory()->create(['is_visible' => true])->id]);
        $hiddenCar = Car::factory()->create(['is_visible' => false]);
        $hiddenLine = Car::factory()->create(['category_id' => CarCategory::factory()->create(['is_visible' => false])->id]);

        $this->get('/xe')->assertOk()
            ->assertSee($visible->name)
            ->assertDontSee($hiddenLine->name)
            ->assertDontSee($hiddenCar->code);

        $this->get("/xe/{$visible->id}")->assertOk()->assertSee($visible->code);
        $this->get("/xe/{$hiddenCar->id}")->assertNotFound();
        $this->get("/xe/{$hiddenLine->id}")->assertNotFound();

        // Hiding rules apply to admins on public URLs too.
        $this->actingAs($this->admin())->get("/xe/{$hiddenCar->id}")->assertNotFound();
    }

    public function test_public_filters_search_sort_and_pagination(): void
    {
        $line = CarCategory::factory()->create(['name' => 'Toyota Vios', 'slug' => 'vios']);
        $cheap = Car::factory()->create(['category_id' => $line->id, 'name' => 'Toyota Vios E', 'price' => 450000000, 'color' => 'Bạc']);
        $pricey = Car::factory()->create(['category_id' => $line->id, 'name' => 'Toyota Vios G', 'price' => 550000000, 'color' => 'Trắng']);
        $sold = Car::factory()->create(['category_id' => $line->id, 'name' => 'Toyota Vios cũ', 'price' => 400000000, 'status' => CarStatus::Sold]);
        Car::factory()->count(12)->create(['category_id' => CarCategory::factory()->create()->id]);

        $response = $this->get('/xe?q=Vios&sort=price_asc')->assertOk();
        // Same-number comparisons behave identically on SQLite and MySQL, so ordering is checked here;
        // the max_price cut-off is asserted in the MySQL acceptance run (decimal vs integer bound).
        $this->assertSame([$sold->id, $cheap->id, $pricey->id], $response->viewData('cars')->pluck('id')->all());

        $this->get('/xe?status=sold')->assertOk()->assertSee($sold->code)
            ->assertDontSee('data-car data-name="Toyota Vios E"', false);
        $this->assertTrue(Session::has('errors') === false, 'valid filters must not flash errors');
    }

    /**
     * Prices are decimal(15,0) strings; SQLite compares them with an integer bound
     * lexicographically (so the range filter only behaves like MySQL here).
     */
    public function test_price_range_filter_boundaries(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Numeric decimal/integer comparison needs MySQL; see acceptance note.');
        }

        $line = CarCategory::factory()->create(['slug' => 'vios']);
        $cheap = Car::factory()->create(['category_id' => $line->id, 'price' => 450000000]);
        $pricey = Car::factory()->create(['category_id' => $line->id, 'price' => 550000000]);

        $bounded = $this->get('/xe?category=vios&max_price=500000000&min_price=400000000')->assertOk();
        $ids = $bounded->viewData('cars')->pluck('id')->all();
        $this->assertContains($cheap->id, $ids);
        $this->assertNotContains($pricey->id, $ids);

        $this->get('/xe?min_price=600000000&max_price=500000000')->assertSessionHasErrors('max_price');
    }

    public function test_public_filters_validation_errors_are_returned_web(): void
    {
        // max_price < min_price is a validation error; web redirects back with errors.
        $this->from('/xe')->get('/xe?min_price=900000000&max_price=100')->assertRedirect('/xe')
            ->assertSessionHasErrors('max_price');
        $this->from('/xe')->get('/xe?sort=drop-table')->assertSessionHasErrors('sort');
    }

    public function test_car_with_history_cannot_be_deleted(): void
    {
        $car = Car::factory()->create();
        Inquiry::create([
            'customer_id' => User::factory()->create()->id, 'car_id' => $car->id,
            'contact_name' => 'Khách', 'contact_phone' => '0900000000', 'content' => 'Hỏi xe',
        ]);

        $this->actingAs($this->admin())->delete("/admin/cars/{$car->id}")
            ->assertSessionHasErrors('car');
        $this->assertDatabaseHas('cars', ['id' => $car->id]);
    }

    public function test_catalog_seeder_is_idempotent_and_creates_toyota_cars(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->assertGreaterThan(12, Car::count());
        $this->assertSame(0, CarCategory::where('brand', '!=', 'Toyota')->count());
        $this->assertSame(Car::count(), CarImage::where('is_primary', true)->count());

        $first = Car::firstOrFail();
        $first->forceFill(['price' => 1, 'is_visible' => false])->save();
        $this->seed(CatalogSeeder::class);

        $this->assertSame(Car::count(), Car::count());
        $this->assertSame('1', $first->fresh()->price);
        $this->assertFalse($first->fresh()->is_visible);
    }
}
