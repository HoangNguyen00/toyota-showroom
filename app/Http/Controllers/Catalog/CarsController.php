<?php

namespace App\Http\Controllers\Catalog;

use App\Enums\CarStatus;
use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Public catalog (guest + any active or guest visitor). Visibility is enforced by the
 * publiclyVisible scope: hidden car OR hidden category → 404, even for admins at public URLs.
 */
class CarsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'status' => ['nullable', Rule::enum(CarStatus::class)],
            'min_price' => ['nullable', 'integer', 'min:0', 'max:999999999999999'],
            'max_price' => ['nullable', 'integer', 'min:0', 'max:999999999999999'],
            'sort' => ['nullable', Rule::in(['newest', 'price_asc', 'price_desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        if (isset($filters['min_price'], $filters['max_price']) && $filters['min_price'] > $filters['max_price']) {
            throw ValidationException::withMessages(['max_price' => 'Giá tối đa phải lớn hơn hoặc bằng giá tối thiểu.']);
        }
        foreach (['min_price', 'max_price'] as $priceKey) {
            if (array_key_exists($priceKey, $filters) && $filters[$priceKey] === null) {
                unset($filters[$priceKey]); // from('')->to(null): absent, not a filter.
            }
        }
        $search = trim($filters['q'] ?? '');

        $cars = Car::query()
            ->publiclyVisible()
            ->with(['category:id,name,slug', 'images'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('color', 'like', '%'.$search.'%');
                });
            })
            ->when(! empty($filters['category']), fn (Builder $query) => $query
                ->whereHas('category', fn (Builder $category) => $category->where('slug', $filters['category'])))
            ->when(! empty($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(isset($filters['min_price']), fn (Builder $query) => $query->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn (Builder $query) => $query->where('price', '<=', $filters['max_price']))
            ->when(($filters['sort'] ?? 'newest') === 'newest', fn (Builder $query) => $query->latest('id'))
            ->when(($filters['sort'] ?? '') === 'price_asc', fn (Builder $query) => $query->orderBy('price'))
            ->when(($filters['sort'] ?? '') === 'price_desc', fn (Builder $query) => $query->orderByDesc('price'))
            ->when(in_array($filters['sort'] ?? '', ['price_asc', 'price_desc'], true), fn (Builder $query) => $query->orderBy('id'))
            ->paginate(12)
            ->withQueryString();

        return view('cars.index', [
            'slides' => collect((require resource_path('data/collection-preview.php'))['slides'])->map(fn (array $slide): array => $slide + [
                'detail_url' => route('cars.index', ['category' => Str::slug($slide['name'])]).'#inventory-title',
                'booking_url' => route('appointments.create'),
            ])->all(),
            'cars' => $cars,
            'categories' => CarCategory::query()->visible()->orderBy('name')->get(['id', 'name', 'slug']),
            'filters' => $filters,
        ]);
    }

    public function show(Car $car): View
    {
        abort_unless(Car::publiclyVisible()->whereKey($car->id)->exists(), 404);
        $car->load(['category:id,name,slug', 'images']);

        return view('cars.show', [
            'car' => $car,
            'related' => Car::publiclyVisible()->whereKeyNot($car->id)->with(['category', 'images'])->latest('id')->limit(3)->get(),
        ]);
    }
}
