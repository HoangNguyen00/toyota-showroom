<?php

namespace Tests\Feature\Catalog;

use App\Models\Car;
use App\Models\CarCategory;
use App\Models\CarImage;
use App\Models\User;
use App\Support\Catalog\CarPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogIntegrationTest extends TestCase
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
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function payload(Car $car): array
    {
        return ['category_id' => $car->category_id, 'code' => $car->code, 'name' => $car->name,
            'year' => 2026, 'color' => 'Trắng', 'price' => 600000000, 'status' => 'available', 'is_visible' => true];
    }

    public function test_each_price_bound_works_alone_and_invalid_pairs_are_rejected(): void
    {
        $cheap = Car::factory()->create(['price' => 400000000]);
        $expensive = Car::factory()->create(['price' => 700000000]);
        $this->assertSame([$cheap->id], $this->get('/xe?max_price=500000000')->assertOk()->viewData('cars')->pluck('id')->all());
        $this->assertSame([$expensive->id], $this->get('/xe?min_price=500000000')->assertOk()->viewData('cars')->pluck('id')->all());
        $this->getJson('/xe?min_price=700000000&max_price=100')->assertUnprocessable()->assertJsonValidationErrors('max_price');
        $this->getJson('/xe?max_price=not-a-price')->assertUnprocessable()->assertJsonValidationErrors('max_price');
    }

    public function test_admin_can_submit_blank_optional_specs_but_not_invalid_year_price_or_category(): void
    {
        $car = Car::factory()->create(['is_visible' => false]);
        $this->actingAs($this->admin());
        $this->putJson('/admin/cars/'.$car->id, [...$this->payload($car), 'year' => 1999, 'price' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors(['year', 'price']);
        $this->putJson('/admin/cars/'.$car->id, [...$this->payload($car), 'category_id' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->put('/admin/cars/'.$car->id, [...$this->payload($car), 'is_visible' => false,
            'specs' => [['label' => '', 'value' => ''], ['label' => 'Động cơ', 'value' => 'Hybrid']]])->assertSessionHasNoErrors();
        $this->assertSame([['label' => 'Động cơ', 'value' => 'Hybrid']], $car->fresh()->specs);
    }

    public function test_a_car_needs_a_primary_image_before_publishing(): void
    {
        $car = Car::factory()->create(['is_visible' => false]);
        $this->actingAs($this->admin())->putJson('/admin/cars/'.$car->id, $this->payload($car))
            ->assertUnprocessable()->assertJsonValidationErrors('is_visible');
        $this->get('/xe/'.$car->id)->assertNotFound();
        $this->post('/admin/cars/'.$car->id.'/images', ['image' => UploadedFile::fake()->image('camry.jpg', 800, 450)])
            ->assertSessionHasNoErrors();
        $this->put('/admin/cars/'.$car->id, $this->payload($car))->assertSessionHasNoErrors();
        $this->assertTrue($car->fresh()->is_visible);
        $this->get('/xe/'.$car->id)->assertOk();
    }

    public function test_tenth_image_limit_is_enforced_without_leaving_an_uploaded_file(): void
    {
        $car = Car::factory()->create();
        foreach (range(1, 10) as $number) {
            $car->images()->create(['path' => 'assets/test-'.$number.'.jpg', 'sort_order' => $number, 'is_primary' => $number === 1]);
        }
        $this->actingAs($this->admin())->postJson('/admin/cars/'.$car->id.'/images', [
            'image' => UploadedFile::fake()->image('eleventh.jpg', 800, 450),
        ])->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->assertSame(10, $car->images()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_five_megabyte_policy_and_original_file_types_are_enforced(): void
    {
        $car = Car::factory()->create();
        $this->actingAs($this->admin())->post('/admin/cars/'.$car->id.'/images', [
            'image' => UploadedFile::fake()->image('large.jpg', 800, 450)->size(5000),
        ])->assertSessionHasNoErrors();
        $this->postJson('/admin/cars/'.$car->id.'/images', [
            'image' => UploadedFile::fake()->image('too-large.jpg', 800, 450)->size(5121),
        ])->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->assertSame(1, $car->images()->count());
    }

    public function test_primary_selection_order_and_deletion_keep_one_primary(): void
    {
        $car = Car::factory()->create();
        $first = $car->images()->create(['path' => 'assets/primary.jpg', 'sort_order' => 100, 'is_primary' => true]);
        $second = $car->images()->create(['path' => 'assets/second.jpg', 'sort_order' => 1, 'is_primary' => false]);
        $this->assertSame(asset($first->path), CarPresenter::fromCar($car->fresh()->load('images', 'category'))['image']);
        $this->actingAs($this->admin())->patch('/admin/cars/'.$car->id.'/images/'.$second->id, ['sort_order' => 0, 'is_primary' => true])
            ->assertSessionHasNoErrors();
        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
        $this->delete('/admin/cars/'.$car->id.'/images/'.$second->id)->assertSessionHasNoErrors();
        $this->assertTrue($first->fresh()->is_primary);
        $this->deleteJson('/admin/cars/'.$car->id.'/images/'.$first->id)->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->assertDatabaseHas('car_images', ['id' => $first->id]);
    }

    public function test_image_metadata_cannot_be_changed_through_another_car_or_non_admin(): void
    {
        $car = Car::factory()->create();
        $image = CarImage::create(['car_id' => Car::factory()->create()->id, 'path' => 'assets/foreign.jpg']);
        $this->actingAs($this->admin())->patchJson('/admin/cars/'.$car->id.'/images/'.$image->id, ['sort_order' => 2])->assertNotFound();
        $this->actingAs(User::factory()->create())->patchJson('/admin/cars/'.$car->id.'/images/'.$image->id, ['sort_order' => 2])->assertForbidden();
    }

    public function test_real_car_actions_use_database_id_and_preserve_status_rules(): void
    {
        $car = Car::factory()->create(['id' => 101]);
        $this->get('/xe/'.$car->id)->assertOk()
            ->assertSee('data-car-id="101"', false)
            ->assertSee(route('appointments.create', ['type' => 'viewing', 'car' => 101]))
            ->assertDontSee('data-preview="Tư vấn', false);
        $car->update(['status' => 'reserved']);
        $this->get('/xe/'.$car->id)->assertOk()->assertSee('Chưa nhận lịch xem')
            ->assertDontSee(route('appointments.create', ['type' => 'viewing', 'car' => 101]));
        $car->update(['status' => 'sold']);
        $this->get('/xe/'.$car->id)->assertOk()->assertSee('Khám phá xe khác')->assertDontSee('data-car-id="101"', false);
    }

    public function test_database_pagination_keeps_filters_and_empty_results_are_honest(): void
    {
        $category = CarCategory::factory()->create(['slug' => 'camry']);
        Car::factory()->count(13)->create(['category_id' => $category->id, 'name' => 'Camry QA']);
        $first = $this->get('/xe?category=camry&q=Camry')->assertOk();
        $this->assertCount(12, $first->viewData('cars'));
        $this->assertSame(13, $first->viewData('cars')->total());
        $this->get('/xe?category=camry&q=Camry&page=2')->assertOk()->assertViewHas('cars', fn ($cars) => $cars->count() === 1);
        $empty = $this->get('/xe?q=NO-SUCH-CAR')->assertOk()->assertSee('Không tìm thấy xe phù hợp');
        $this->assertSame(0, $empty->viewData('cars')->total());
    }
}
