<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * P2 đề xuất — Xe quan tâm. Danh sách ID nằm ở localStorage của trình duyệt;
 * server chỉ render thẻ cho xe còn công khai (Car::publiclyVisible), không lưu DB/session.
 */
class SavedCarsController extends Controller
{
    public const LIMIT = 12;

    public function index(): View
    {
        return view('cars.saved', [
            'limit' => self::LIMIT,
            'compareUrl' => Route::has('cars.compare') ? route('cars.compare') : null,
        ]);
    }

    public function cards(Request $request): JsonResponse
    {
        $raw = $request->query('ids', '');
        if (! is_string($raw) || ($raw !== '' && ! preg_match('/^[1-9]\d{0,9}(,[1-9]\d{0,9})*$/', $raw))) {
            throw ValidationException::withMessages(['ids' => 'Danh sách xe không hợp lệ.']);
        }

        $ids = $raw === '' ? [] : array_slice(array_values(array_unique(array_map('intval', explode(',', $raw)))), 0, self::LIMIT);
        if ($ids === []) {
            return response()->json(['ids' => [], 'html' => '']);
        }

        $found = Car::query()->publiclyVisible()
            ->with(['category:id,name,slug', 'images'])
            ->whereKey($ids)->get()->keyBy('id');
        // Giữ thứ tự khách đã lưu; xe ẩn/dòng ẩn/đã xóa bị loại.
        $cars = collect($ids)->filter(fn (int $id): bool => $found->has($id))->map(fn (int $id): Car => $found[$id])->values();

        return response()->json([
            'ids' => $cars->pluck('id')->all(),
            'html' => view('cars.partials.saved-cards', [
                'cars' => $cars,
                'compareUrl' => Route::has('cars.compare') ? route('cars.compare') : null,
            ])->render(),
        ]);
    }
}
