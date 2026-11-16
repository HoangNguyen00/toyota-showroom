<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Service::class);

        return view('admin.services.index', [
            'services' => Service::query()->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Service::class);

        return view('admin.services.form', ['service' => new Service]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $data = $request->safe()->all();
        $data['slug'] = Str::slug($data['name']);
        Service::create($data);

        return redirect()->route('admin.services.index')->with('status', 'Đã tạo dịch vụ.');
    }

    public function edit(Service $service): View
    {
        Gate::authorize('update', $service);

        return view('admin.services.form', ['service' => $service]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->safe()->all();
        $data['slug'] = Str::slug($data['name']);
        $service->update($data);

        return redirect()->route('admin.services.index')->with('status', 'Đã cập nhật dịch vụ.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        Gate::authorize('delete', $service);

        // Data-safety: a service referenced by appointments cannot be hard-deleted.
        if ($service->appointments()->exists()) {
            return redirect()->route('admin.services.index')
                ->with('error', 'Dịch vụ đã có lịch hẹn liên quan, không thể xóa. Hãy tắt hiển thị thay vì xóa.');
        }

        $service->delete();

        return redirect()->route('admin.services.index')->with('status', 'Đã xóa dịch vụ.');
    }
}
