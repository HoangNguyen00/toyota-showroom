<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\CarCategoryRequest;
use App\Models\CarCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Admin car-line (category) management. Brand is always Toyota and is set by the server.
 */
class AdminCarCategoryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CarCategory::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = trim($filters['q'] ?? '');

        return view('admin.car-categories.index', [
            'categories' => CarCategory::query()
                ->withCount('cars')
                ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', CarCategory::class);

        return view('admin.car-categories.form', ['category' => new CarCategory]);
    }

    public function store(CarCategoryRequest $request): RedirectResponse
    {
        CarCategory::create($request->categoryAttributes());

        return redirect()->route('admin.car-categories.index')->with('status', 'Đã thêm dòng xe Toyota.');
    }

    public function edit(CarCategory $category): View
    {
        Gate::authorize('update', $category);

        return view('admin.car-categories.form', ['category' => $category]);
    }

    public function update(CarCategoryRequest $request, CarCategory $category): RedirectResponse
    {
        Gate::authorize('update', $category);
        $category->update($request->categoryAttributes());

        return redirect()->route('admin.car-categories.index')->with('status', 'Đã cập nhật dòng xe.');
    }

    public function destroy(CarCategory $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        try {
            $category->delete();
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'category' => 'Dòng xe vẫn còn xe hoặc dữ liệu tham chiếu nên không xóa được.',
            ]);
        }

        return redirect()->route('admin.car-categories.index')->with('status', 'Đã xóa dòng xe.');
    }
}
