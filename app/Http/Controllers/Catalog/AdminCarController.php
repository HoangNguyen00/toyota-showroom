<?php

namespace App\Http\Controllers\Catalog;

use App\Enums\CarStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\CarRequest;
use App\Models\Car;
use App\Models\CarCategory;
use App\Services\Catalog\SaveCar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Admin car management: list/search/filter/pagination, create, edit, delete.
 * Route group already enforces web/auth/active/role:admin; the Policy is checked per record.
 */
class AdminCarController extends Controller
{
    private const STATUS_LABELS = [
        'available' => 'Còn xe',
        'reserved' => 'Đã giữ',
        'sold' => 'Đã bán',
    ];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Car::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::enum(CarStatus::class)],
            'visible' => ['nullable', Rule::in(['0', '1'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = trim($filters['q'] ?? '');

        $cars = Car::query()
            ->with(['category:id,name', 'images'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%');
                });
            })
            ->when(! empty($filters['category']), fn (Builder $query) => $query->where('category_id', $filters['category']))
            ->when(! empty($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(isset($filters['visible']) && $filters['visible'] !== '', fn (Builder $query) => $query->where('is_visible', $filters['visible'] === '1'))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.cars.index', [
            'cars' => $cars,
            'categories' => CarCategory::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Car::class);

        return view('admin.cars.create', [
            'categories' => CarCategory::query()->orderBy('name')->get(['id', 'name', 'is_visible']),
            'statuses' => CarStatus::cases(),
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    public function store(CarRequest $request, SaveCar $service): RedirectResponse
    {
        $car = $service->create($request->safe()->only([
            'category_id', 'code', 'name', 'year', 'color', 'price', 'status', 'specs', 'is_visible',
        ]));

        return redirect()->route('admin.cars.edit', $car)
            ->with('status', 'Đã thêm xe. Có thể tải ảnh cho xe này.');
    }

    public function edit(Car $car): View
    {
        Gate::authorize('update', $car);

        return view('admin.cars.edit', [
            'car' => $car->load('images'),
            'categories' => CarCategory::query()->orderBy('name')->get(['id', 'name', 'is_visible']),
            'statuses' => CarStatus::cases(),
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    public function update(CarRequest $request, Car $car, SaveCar $service): RedirectResponse
    {
        Gate::authorize('update', $car);
        $service->update($car, $request->safe()->only([
            'category_id', 'code', 'name', 'year', 'color', 'price', 'status', 'specs', 'is_visible',
        ]));

        return redirect()->route('admin.cars.edit', $car)->with('status', 'Đã cập nhật xe.');
    }

    public function destroy(Car $car, SaveCar $service): RedirectResponse
    {
        Gate::authorize('delete', $car);

        try {
            $deleted = $service->delete($car);
        } catch (QueryException) {
            $deleted = false;
        }

        if (! $deleted) {
            throw ValidationException::withMessages([
                'car' => 'Xe đã có hồ sơ tư vấn hoặc lịch hẹn nên không xóa được. Hãy ẩn xe thay vì xóa.',
            ]);
        }

        return redirect()->route('admin.cars.index')->with('status', 'Đã xóa xe.');
    }
}
