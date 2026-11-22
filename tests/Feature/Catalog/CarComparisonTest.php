<?php

namespace Tests\Feature\Catalog;

use App\Enums\CarStatus;
use App\Models\Car;
use App\Models\CarCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P2 đề xuất: so sánh 2 xe công khai. Chỉ đọc, dùng lại publiclyVisible + CarPresenter.
 */
class CarComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function car(array $overrides = []): Car
    {
        return Car::factory()->create($overrides);
    }

    public function test_selection_page_lists_only_public_cars_with_code_and_color(): void
    {
        $category = CarCategory::factory()->create(['name' => 'Vios']);
        $this->car(['category_id' => $category->id, 'name' => 'Toyota Vios 1.5G', 'code' => 'VIOS-01', 'color' => 'Trắng']);
        $this->car(['category_id' => $category->id, 'name' => 'Toyota Vios 1.5G', 'code' => 'VIOS-02', 'color' => 'Đỏ']);
        $this->car(['is_visible' => false, 'code' => 'HIDDEN-01']);
        $this->car([
            'category_id' => CarCategory::factory()->create(['is_visible' => false])->id,
            'code' => 'HIDDENCAT-01',
        ]);

        $this->get('/xe/so-sanh')
            ->assertOk()
            ->assertSee('Toyota Vios 1.5G · VIOS-01 · Trắng')
            ->assertSee('Toyota Vios 1.5G · VIOS-02 · Đỏ')
            ->assertDontSee('HIDDEN-01')
            ->assertDontSee('HIDDENCAT-01')
            ->assertViewHas('comparison', null);
    }

    public function test_catalog_links_to_comparison(): void
    {
        $car = $this->car(['code' => 'LINK-01']);

        $this->get('/xe')->assertOk()
            ->assertSee(route('cars.compare'), false)
            ->assertSee(route('cars.compare', ['xe1' => $car->id]), false);
    }

    public function test_guest_compares_two_public_cars_in_selected_order(): void
    {
        $first = $this->car([
            'name' => 'Toyota Camry 2.5Q', 'code' => 'CAMRY-01', 'color' => 'Đen', 'year' => 2026,
            'price' => 1_460_000_000, 'status' => CarStatus::Available,
            'specs' => [['label' => 'Động cơ', 'value' => '2.5L'], ['label' => 'Số chỗ', 'value' => '5']],
        ]);
        $second = $this->car([
            'name' => 'Toyota Vios 1.5G', 'code' => 'VIOS-03', 'color' => 'Đen', 'year' => 2025,
            'price' => 545_000_000, 'status' => CarStatus::Reserved,
            'specs' => [['label' => 'Động cơ', 'value' => '1.5L']],
        ]);

        $response = $this->get('/xe/so-sanh?xe1='.$second->id.'&xe2='.$first->id)->assertOk();

        $response->assertViewHas('comparison', function (array $comparison) use ($first, $second): bool {
            $rows = collect($comparison['rows'])->keyBy('label');

            return $comparison['cars'][0]['id'] === $second->id
                && $comparison['cars'][1]['id'] === $first->id
                && $rows['Màu ngoại thất']['different'] === false
                && $rows['Năm sản xuất']['different'] === true
                && $rows['Giá công bố tham khảo']['different'] === true
                && $rows['Động cơ']['values'] === ['1.5L', '2.5L']
                && $rows['Số chỗ']['values'] === ['Đang cập nhật', '5']
                && $rows['Số chỗ']['different'] === true;
        });
        $response->assertSee('Đang cập nhật')
            ->assertSee(route('cars.show', $first), false)
            ->assertSee(route('cars.show', $second), false)
            ->assertSeeInOrder(['VIOS-03', 'CAMRY-01']);
    }

    public function test_specs_duplicating_base_fields_are_not_repeated(): void
    {
        $category = CarCategory::factory()->create(['name' => 'Corolla Cross']);
        $first = $this->car(['category_id' => $category->id, 'color' => 'Trắng', 'specs' => [
            ['label' => 'Dòng xe', 'value' => 'Corolla Cross'], ['label' => 'Màu ngoại thất', 'value' => 'Trắng'],
        ]]);
        $second = $this->car(['specs' => null]);

        $this->get('/xe/so-sanh?xe1='.$first->id.'&xe2='.$second->id)
            ->assertOk()
            ->assertViewHas('comparison', function (array $comparison): bool {
                $labels = collect($comparison['rows'])->pluck('label');

                return $labels->filter(fn ($label) => $label === 'Dòng xe')->count() === 1
                    && $labels->filter(fn ($label) => $label === 'Màu ngoại thất')->count() === 1;
            });
    }

    public function test_rejects_same_car_twice_and_keeps_selection(): void
    {
        $car = $this->car(['code' => 'SAME-01']);

        $this->get('/xe/so-sanh?xe1='.$car->id.'&xe2='.$car->id)
            ->assertStatus(422)
            ->assertSee('Hãy chọn hai chiếc xe khác nhau.')
            ->assertViewHas('comparison', null)
            ->assertViewHas('selected', ['xe1' => (string) $car->id, 'xe2' => (string) $car->id])
            ->assertSee('<option value="'.$car->id.'" selected', false);
    }

    public function test_rejects_invalid_or_missing_ids(): void
    {
        $car = $this->car();

        foreach ([
            '?xe1=abc&xe2='.$car->id,
            '?xe1='.$car->id.'&xe2=-5',
            '?xe1='.$car->id.'&xe2=999999',
            '?xe1[]=1&xe2='.$car->id,
            '?xe1='.$car->id.'&xe2=',
        ] as $query) {
            $this->get('/xe/so-sanh'.$query)
                ->assertStatus(422)
                ->assertViewHas('comparison', null);
        }
    }

    public function test_rejects_hidden_car_or_car_in_hidden_category(): void
    {
        $public = $this->car();
        $hidden = $this->car(['is_visible' => false]);
        $inHiddenCategory = $this->car([
            'category_id' => CarCategory::factory()->create(['is_visible' => false])->id,
        ]);

        foreach ([$hidden, $inHiddenCategory] as $blocked) {
            $this->get('/xe/so-sanh?xe1='.$public->id.'&xe2='.$blocked->id)
                ->assertStatus(422)
                ->assertSee('Xe không tồn tại hoặc không còn hiển thị.')
                ->assertDontSee($blocked->code)
                ->assertViewHas('comparison', null);
        }
    }

    public function test_single_preselected_car_shows_form_without_errors(): void
    {
        $car = $this->car();

        $this->get('/xe/so-sanh?xe1='.$car->id)
            ->assertOk()
            ->assertSessionHasNoErrors()
            ->assertViewHas('selected', ['xe1' => (string) $car->id, 'xe2' => null])
            ->assertViewHas('comparison', null);
    }
}
