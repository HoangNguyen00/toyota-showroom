<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'conversation' => ['nullable', 'integer', 'min:1']]);
        $query = Inquiry::query()->where('customer_id', $request->user()->id)
            ->select(['id', 'customer_id', 'car_id', 'assigned_to', 'status', 'access_version', 'content', 'created_at'])
            ->with(['assignedStaff:id,name', 'car:id,name']);
        $conversations = (clone $query)->when(filled($filters['q'] ?? ''), function ($query) use ($filters): void {
            $query->where(function ($query) use ($filters): void {
                $query->where('content', 'like', '%'.$filters['q'].'%')
                    ->orWhereHas('car', fn ($car) => $car->where('name', 'like', '%'.$filters['q'].'%'));
            });
        })->latest('id')->paginate(15)->withQueryString();
        $selected = isset($filters['conversation']) ? (clone $query)->findOrFail($filters['conversation']) : $conversations->first();
        $threads = $conversations->getCollection()->keyBy('id');
        if ($selected && ! $threads->has($selected->id)) {
            $threads->put($selected->id, $selected);
        }

        return view('customer.chat', compact('conversations', 'threads', 'selected'));
    }
}
