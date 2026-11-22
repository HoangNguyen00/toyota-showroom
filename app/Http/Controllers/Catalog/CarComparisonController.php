<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Support\Catalog\CarComparison;
use App\Support\Catalog\CarPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

/**
 * P2 đề xuất — so sánh 2 xe công khai (chỉ đọc, không schema mới).
 * Quy tắc hiển thị dùng chung Car::publiclyVisible(): xe ẩn hoặc dòng ẩn bị từ chối.
 */
class CarComparisonController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $selected = [
            'xe1' => is_scalar($request->query('xe1')) ? (string) $request->query('xe1') : null,
            'xe2' => is_scalar($request->query('xe2')) ? (string) $request->query('xe2') : null,
        ];
        $errors = new MessageBag;
        $comparison = null;

        // Chỉ so sánh khi form gửi ô xe thứ hai; link từ Catalog chỉ chọn sẵn xe1.
        if ($request->has('xe2')) {
            $validator = Validator::make($request->query(), [
                'xe1' => ['required', 'integer', 'min:1', 'max:4294967295'],
                'xe2' => ['required', 'integer', 'min:1', 'max:4294967295', 'different:xe1'],
            ], [
                'xe1.required' => 'Chọn chiếc xe thứ nhất.',
                'xe2.required' => 'Chọn chiếc xe thứ hai.',
                'xe1.*' => 'Xe thứ nhất không hợp lệ.',
                'xe2.different' => 'Hãy chọn hai chiếc xe khác nhau.',
                'xe2.*' => 'Xe thứ hai không hợp lệ.',
            ]);
            $errors = $validator->errors();

            if ($errors->isEmpty()) {
                $ids = [(int) $selected['xe1'], (int) $selected['xe2']];
                $cars = Car::query()->publiclyVisible()
                    ->with(['category:id,name,slug', 'images'])
                    ->whereKey($ids)->get()->keyBy('id');

                foreach (['xe1' => $ids[0], 'xe2' => $ids[1]] as $field => $id) {
                    if (! $cars->has($id)) {
                        $errors->add($field, 'Xe không tồn tại hoặc không còn hiển thị.');
                    }
                }

                if ($errors->isEmpty()) {
                    $comparison = CarComparison::build([
                        CarPresenter::fromCar($cars[$ids[0]]),
                        CarPresenter::fromCar($cars[$ids[1]]),
                    ]);
                }
            }
        }

        $options = Car::query()->publiclyVisible()
            ->with('category:id,name')
            ->orderBy('category_id')->orderBy('name')->orderBy('code')
            ->get(['id', 'category_id', 'name', 'code', 'color'])
            ->groupBy(fn (Car $car): string => $car->category?->name ?? 'Toyota');

        return response()->view('cars.compare', [
            'options' => $options,
            'selected' => $selected,
            'comparison' => $comparison,
            'compareErrors' => $errors,
        ], $errors->isEmpty() ? 200 : 422);
    }
}
