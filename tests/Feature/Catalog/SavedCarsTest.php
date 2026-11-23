<?php

namespace Tests\Feature\Catalog;

use App\Http\Controllers\Catalog\SavedCarsController;
use App\Models\Car;
use App\Models\CarCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * P2 đề xuất: xe quan tâm lưu ID theo trình duyệt; server chỉ trả xe công khai.
 */
class SavedCarsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_saved_page_renders_shell_with_empty_state_and_limit(): void
    {
        $this->get('/xe-quan-tam')
            ->assertOk()
            ->assertSee('data-saved-cars-page', false)
            ->assertSee('data-saved-limit="'.SavedCarsController::LIMIT.'"', false)
            ->assertSee('Chưa có xe quan tâm')
            ->assertSee(route('cars.saved.cards'), false);
    }

    public function test_cards_endpoint_returns_only_public_cars_in_saved_order(): void
    {
        $first = Car::factory()->create(['code' => 'SAVED-01']);
        $second = Car::factory()->create(['code' => 'SAVED-02']);
        $hidden = Car::factory()->create(['code' => 'SAVED-HIDDEN', 'is_visible' => false]);
        $hiddenCategory = Car::factory()->create([
            'code' => 'SAVED-HIDDENCAT',
            'category_id' => CarCategory::factory()->create(['is_visible' => false])->id,
        ]);
        $deletedId = 999999;

        $ids = implode(',', [$second->id, $hidden->id, $deletedId, $first->id, $hiddenCategory->id]);
        $response = $this->getJson('/xe-quan-tam/the-xe?ids='.$ids)->assertOk();

        $this->assertSame([$second->id, $first->id], $response->json('ids'));
        $html = $response->json('html');
        $this->assertStringContainsString('SAVED-02', $html);
        $this->assertStringContainsString('SAVED-01', $html);
        $this->assertStringNotContainsString('SAVED-HIDDEN', $html);
        $this->assertStringNotContainsString('SAVED-HIDDENCAT', $html);
        $this->assertLessThan(strpos($html, 'SAVED-01'), strpos($html, 'SAVED-02'));
        $this->assertStringContainsString('data-save-car', $html);
    }

    public function test_cards_endpoint_deduplicates_and_caps_ids(): void
    {
        $cars = Car::factory()->count(SavedCarsController::LIMIT + 3)->create();
        $ids = $cars->pluck('id');
        $query = implode(',', $ids->prepend($ids->first())->all());

        $response = $this->getJson('/xe-quan-tam/the-xe?ids='.$query)->assertOk();

        $this->assertCount(SavedCarsController::LIMIT, $response->json('ids'));
        $this->assertSame($cars->first()->id, $response->json('ids.0'));
        $this->assertSame(array_values(array_unique($response->json('ids'))), $response->json('ids'));
    }

    public function test_cards_endpoint_rejects_malformed_ids(): void
    {
        foreach (['abc', '1,,2', '-1', '1;DROP', str_repeat('9', 30)] as $bad) {
            $this->getJson('/xe-quan-tam/the-xe?ids='.urlencode($bad))->assertUnprocessable();
        }
        $this->getJson('/xe-quan-tam/the-xe?ids[]=1')->assertUnprocessable();
        $this->getJson('/xe-quan-tam/the-xe')
            ->assertOk()->assertJson(['ids' => [], 'html' => '']);
    }

    public function test_catalog_card_and_detail_show_save_toggle_and_saved_link(): void
    {
        $car = Car::factory()->create();

        $this->get('/xe')->assertOk()
            ->assertSee('data-save-car', false)
            ->assertSee('data-save-car-id="'.$car->id.'"', false)
            ->assertSee(route('cars.saved'), false);
        $this->get('/xe/'.$car->id)->assertOk()
            ->assertSee('data-save-car', false)
            ->assertSee(route('cars.saved'), false);
    }

    public function test_compare_selection_appears_only_when_compare_route_exists(): void
    {
        Car::factory()->create();
        // Chạy đúng cả trước và sau khi PR So sánh 2 xe được merge.
        if (! Route::has('cars.compare')) {
            $this->get('/xe-quan-tam')->assertOk()->assertDontSee('data-saved-compare', false);

            Route::get('/xe/so-sanh', fn () => 'compare')->name('cars.compare');
            Route::getRoutes()->refreshNameLookups();
        }

        $this->get('/xe-quan-tam')->assertOk()->assertSee('data-saved-compare', false)
            ->assertSee('data-compare-url="'.route('cars.compare').'"', false);
    }
}
