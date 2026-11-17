<?php

namespace App\Http\Controllers\Inquiry;

use App\Enums\InquiryStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inquiry\ManageInquiryRequest;
use App\Http\Requests\Inquiry\StoreInquiryRequest;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\Inquiry\InquiryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    public function store(StoreInquiryRequest $request, InquiryService $service)
    {
        $inquiry = $service->create($request->user(), $request->validated());

        return response()->json(['id' => $inquiry->id, 'message' => 'Đã tiếp nhận yêu cầu tư vấn.'], 201);
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Inquiry::class);
        $filters = $request->validate(['status' => ['nullable', Rule::enum(InquiryStatus::class)]]);
        $user = $request->user();
        $query = Inquiry::with(['assignedStaff:id,name', 'car:id,name']);
        if ($user->role === UserRole::Customer) {
            $query->where('customer_id', $user->id);
        } elseif ($user->role === UserRole::Sales) {
            $query->where('assigned_to', $user->id);
        }
        $inquiries = $query->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('id')->paginate(15)->withQueryString();
        if ($request->expectsJson()) {
            return response()->json([
                'data' => $inquiries->map(fn ($item) => $this->data($item, $user)),
                'current_page' => $inquiries->currentPage(), 'last_page' => $inquiries->lastPage(),
            ]);
        }

        return view('staff.inquiries.index', compact('inquiries'));
    }

    public function show(Request $request, Inquiry $inquiry)
    {
        Gate::authorize('view', $inquiry);
        $inquiry->load(['assignedStaff:id,name', 'car:id,name']);
        if ($request->expectsJson()) {
            return response()->json($this->data($inquiry, $request->user()));
        }
        $sales = $request->user()->can('assign', $inquiry)
            ? User::where('role', UserRole::Sales)->where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect();

        return view('staff.inquiries.show', compact('inquiry', 'sales'));
    }

    public function update(ManageInquiryRequest $request, Inquiry $inquiry, InquiryService $service)
    {
        $action = last(explode('.', $request->route()->getName()));
        $updated = $service->update($request->user(), $inquiry, $action, $request->validated());
        if ($request->expectsJson()) {
            return response()->json($this->data($updated->load(['assignedStaff:id,name', 'car:id,name']), $request->user()));
        }

        return back()->with('status', 'Đã cập nhật hồ sơ.');
    }

    private function data(Inquiry $inquiry, User $user): array
    {
        $data = [
            'id' => $inquiry->id, 'content' => $inquiry->content,
            'status' => $inquiry->status->value, 'close_result' => $inquiry->close_result,
            'car' => $inquiry->car?->only(['id', 'name']),
            'assigned_staff' => $inquiry->assignedStaff?->only(['id', 'name']),
            'access_version' => $inquiry->access_version,
            'channel' => $inquiry->customer_id ? 'inquiry.'.$inquiry->id.'.v'.$inquiry->access_version : null,
            'created_at' => $inquiry->created_at->toIso8601String(),
        ];
        if ($user->can('manage', $inquiry)) {
            $data += $inquiry->only(['contact_name', 'contact_phone', 'contact_email', 'internal_note']);
        }

        return $data;
    }
}
