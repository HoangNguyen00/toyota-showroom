<?php

namespace Tests\Feature\Appointment;

use App\Enums\AppointmentStatus;
use App\Enums\CarStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        // Các test này gửi nhiều POST liên tiếp; throttle riêng đã có test riêng
        // nên bỏ middleware throttle để không bị 429 giữa chuỗi request.
        $this->withoutMiddleware([ThrottleRequests::class]);
    }

    private function user(UserRole $role = UserRole::Customer): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function car(): Car
    {
        $existing = Car::where('code', 'TOY-TEST-001')->first();
        if ($existing !== null) {
            return $existing;
        }
        $category = CarCategory::query()->firstWhere('slug', 'vios')
            ?? CarCategory::create([
                'brand' => 'Toyota',
                'name' => 'Vios', 'slug' => 'vios', 'is_visible' => true,
            ]);

        return Car::create([
            'code' => 'TOY-TEST-001', 'slug' => 'toy-test-001', 'name' => 'Toyota Vios 1.5G',
            'category_id' => $category->id, 'status' => CarStatus::Available,
            'is_visible' => true, 'year' => (int) date('Y'), 'color' => 'Trắng',
            'price' => 539000000,
        ]);
    }

    private function bookableService(): Service
    {
        return Service::create([
            'name' => 'Bảo dưỡng định kỳ 10.000km', 'slug' => 'bao-duong-10k',
            'type' => 'maintenance', 'description' => 'Kiểm tra và bảo dưỡng định kỳ.',
            'allow_booking' => true, 'is_visible' => true,
        ]);
    }

    private function viewingPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'viewing',
            'car_id' => $this->car()->id,
            'contact_name' => 'Nguyễn Văn A',
            'contact_phone' => '0901234567',
            'requested_at' => now()->addDays(2)->setTime(9, 0)->format('Y-m-d H:i'),
        ], $overrides);
    }

    public function test_guest_and_customer_can_book_a_viewing(): void
    {
        $this->post('/dat-lich', $this->viewingPayload())->assertRedirect('/dat-lich');
        $this->assertDatabaseHas('appointments', [
            'type' => 'viewing', 'status' => 'pending', 'customer_id' => null,
            'contact_name' => 'Nguyễn Văn A',
        ]);

        $this->actingAs($this->user())
            ->post('/dat-lich', $this->viewingPayload(['contact_phone' => '0901112223']))
            ->assertRedirect();
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_booking_validates_type_specific_data(): void
    {
        // Past time rejected first (time errors surface before type-specific ones).
        $this->post('/dat-lich', $this->viewingPayload([
            'requested_at' => now()->subDay()->format('Y-m-d H:i'),
        ]))->assertInvalid(['requested_at']);

        // Viewing: car_id required, customer_car_info rejected.
        // Web form redirects back with session errors (not JSON 422).
        $this->post('/dat-lich', $this->viewingPayload([
            'car_id' => 999999,
        ]))->assertInvalid(['car_id']);
        $this->post('/dat-lich', $this->viewingPayload([
            'customer_car_info' => 'Vios 2020',
        ]))->assertInvalid(['customer_car_info']);

        // Maintenance: service_id required from bookable maintenance services.
        $this->post('/dat-lich', $this->viewingPayload([
            'type' => 'maintenance', 'car_id' => null, 'service_id' => $this->bookableService()->id,
        ]))->assertInvalid(['customer_car_info']);
        $this->post('/dat-lich', [
            'type' => 'maintenance', 'service_id' => 999999,
            'contact_name' => 'B', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDay()->format('Y-m-d H:i'),
        ])->assertInvalid(['service_id', 'customer_car_info']);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_booking_rejects_hidden_or_unavailable_car(): void
    {
        $car = $this->car();
        $car->update(['is_visible' => false]);
        $this->post('/dat-lich', $this->viewingPayload(['car_id' => $car->id]))->assertInvalid(['car_id']);

        $car->update(['is_visible' => true, 'status' => CarStatus::Reserved]);
        $this->post('/dat-lich', $this->viewingPayload(['car_id' => $car->id]))->assertInvalid(['car_id']);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_customer_cannot_book_overlapping_time_slot(): void
    {
        $customer = $this->user();
        $this->actingAs($customer)->post('/dat-lich', $this->viewingPayload());

        // Lịch thứ hai cách 30 phút: bị chặn.
        $this->actingAs($customer)
            ->post('/dat-lich', $this->viewingPayload([
                'requested_at' => now()->addDays(2)->setTime(9, 30)->format('Y-m-d H:i'),
            ]))
            ->assertInvalid(['requested_at']);
        $this->assertDatabaseCount('appointments', 1);

        // Cách đúng 60 phút: vẫn bị chặn (phải cách hơn 1 tiếng).
        $this->actingAs($customer)
            ->post('/dat-lich', $this->viewingPayload([
                'requested_at' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i'),
            ]))
            ->assertInvalid(['requested_at']);
        $this->assertDatabaseCount('appointments', 1);

        // Cách hơn 1 tiếng (10:31): hợp lệ.
        $this->actingAs($customer)
            ->post('/dat-lich', $this->viewingPayload([
                'requested_at' => now()->addDays(2)->setTime(10, 31)->format('Y-m-d H:i'),
            ]))
            ->assertRedirect();
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_guest_with_same_phone_cannot_book_overlapping_slot(): void
    {
        // Guest cùng SĐT: đặt 11:20 rồi 11:40 phải bị chặn.
        $this->post('/dat-lich', $this->viewingPayload())->assertRedirect();
        $this->post('/dat-lich', $this->viewingPayload([
            'requested_at' => now()->addDays(2)->setTime(9, 40)->format('Y-m-d H:i'),
        ]))->assertInvalid(['requested_at']);
        $this->assertDatabaseCount('appointments', 1);

        // SĐT khác cùng giờ: vẫn đặt được (khác người).
        $this->post('/dat-lich', $this->viewingPayload([
            'contact_name' => 'Trần Khác',
            'contact_phone' => '0909876543',
        ]))->assertRedirect();
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_customer_cannot_reschedule_into_overlapping_slot(): void
    {
        $customer = $this->user();
        $first = Appointment::create([
            'type' => 'viewing', 'customer_id' => $customer->id,
            'contact_name' => 'A', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDays(2)->setTime(9, 0), 'status' => 'pending',
        ]);
        $second = Appointment::create([
            'type' => 'viewing', 'customer_id' => $customer->id,
            'contact_name' => 'B', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDays(2)->setTime(11, 0), 'status' => 'pending',
        ]);

        // Đổi lịch thứ nhất sang 11:30 — cách lịch 11:00 dưới 60 phút: chặn.
        $this->actingAs($customer)
            ->post("/lich-hen/{$first->id}/doi", ['requested_at' => now()->addDays(2)->setTime(11, 30)->format('Y-m-d H:i')])
            ->assertRedirect()->assertSessionHasErrors('requested_at');
        $this->assertSame('09:00', $first->fresh()->requested_at->format('H:i'));

        // Đổi sang 12:01 — cách hơn 1 tiếng: hợp lệ.
        $this->actingAs($customer)
            ->post("/lich-hen/{$first->id}/doi", ['requested_at' => now()->addDays(2)->setTime(12, 1)->format('Y-m-d H:i')])
            ->assertRedirect();
        $this->assertSame('12:01', $first->fresh()->requested_at->format('H:i'));

        $this->assertDatabaseHas('appointments', ['id' => $second->id, 'requested_at' => now()->addDays(2)->setTime(11, 0)]);
    }

    public function test_customer_sees_only_own_appointments(): void
    {
        $customer = $this->user();
        $other = $this->user();

        $own = Appointment::create([
            'type' => 'viewing', 'customer_id' => $customer->id,
            'contact_name' => 'A', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);
        Appointment::create([
            'type' => 'viewing', 'customer_id' => $other->id,
            'contact_name' => 'B', 'contact_phone' => '0901234568',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);

        $this->actingAs($customer)->get('/lich-hen')
            ->assertOk()
            ->assertViewHas('appointments', fn ($page) => $page->total() === 1 && $page->first()->id === $own->id);
    }

    public function test_customer_reschedules_own_pending_appointment(): void
    {
        $customer = $this->user();
        $appointment = Appointment::create([
            'type' => 'viewing', 'customer_id' => $customer->id,
            'contact_name' => 'A', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);
        $newTime = now()->addDays(3)->setTime(14, 0);

        $this->actingAs($customer)
            ->post("/lich-hen/{$appointment->id}/doi", ['requested_at' => $newTime->format('Y-m-d H:i')])
            ->assertRedirect();
        $this->assertSame($newTime->format('Y-m-d H:i'), $appointment->fresh()->requested_at->format('Y-m-d H:i'));
        $this->assertSame(AppointmentStatus::Pending, $appointment->fresh()->status);

        // Cannot reschedule someone else's appointment.
        $other = $this->user();
        $foreign = Appointment::create([
            'type' => 'viewing', 'customer_id' => $other->id,
            'contact_name' => 'B', 'contact_phone' => '0901234568',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);
        $this->actingAs($customer)
            ->post("/lich-hen/{$foreign->id}/doi", ['requested_at' => $newTime->format('Y-m-d H:i')])
            ->assertForbidden();

        // Past new time rejected.
        $this->actingAs($customer)
            ->post("/lich-hen/{$appointment->id}/doi", ['requested_at' => now()->subHour()->format('Y-m-d H:i')])
            ->assertRedirect()->assertSessionHasErrors('requested_at');
    }

    public function test_customer_cannot_reschedule_after_time(): void
    {
        $customer = $this->user();
        $passed = Appointment::create([
            'type' => 'viewing', 'customer_id' => $customer->id,
            'contact_name' => 'A', 'contact_phone' => '0901234567',
            'requested_at' => now()->subHour(), 'status' => 'pending',
        ]);

        $this->actingAs($customer)
            ->post("/lich-hen/{$passed->id}/doi", ['requested_at' => now()->addDay()->format('Y-m-d H:i')])
            ->assertRedirect()->assertSessionHasErrors('status');
        $this->assertSame(now()->subHour()->format('Y-m-d H:i'), $passed->fresh()->requested_at->format('Y-m-d H:i'));
    }

    public function test_staff_reschedules_pending_appointment(): void
    {
        $sales = $this->user(UserRole::Sales);
        $other = $this->user(UserRole::Sales);
        $appointment = Appointment::create([
            'type' => 'viewing', 'assigned_to' => $sales->id,
            'contact_name' => 'A', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);
        $newTime = now()->addDays(2)->setTime(10, 30);

        // Sales on own appointment: allowed.
        $this->actingAs($sales)
            ->post("/staff/appointments/{$appointment->id}/doi", ['requested_at' => $newTime->format('Y-m-d H:i')])
            ->assertRedirect();
        $this->assertSame($newTime->format('Y-m-d H:i'), $appointment->fresh()->requested_at->format('Y-m-d H:i'));

        // Sales on someone else's appointment: forbidden.
        $foreign = Appointment::create([
            'type' => 'viewing', 'assigned_to' => $other->id,
            'contact_name' => 'C', 'contact_phone' => '0901234569',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);
        $this->actingAs($sales)
            ->post("/staff/appointments/{$foreign->id}/doi", ['requested_at' => $newTime->format('Y-m-d H:i')])
            ->assertForbidden();

        // Confirmed appointment cannot be rescheduled (must cancel first).
        $foreign->update(['status' => 'confirmed', 'confirmed_at' => now()->addDay()]);
        $this->actingAs($other)
            ->post("/staff/appointments/{$foreign->id}/doi", ['requested_at' => $newTime->format('Y-m-d H:i')])
            ->assertRedirect()->assertSessionHasErrors('status');
    }

    public function test_customer_can_cancel_own_pending_appointment_before_time(): void
    {
        $customer = $this->user();
        $appointment = Appointment::create([
            'type' => 'viewing', 'customer_id' => $customer->id,
            'contact_name' => 'A', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);

        $this->actingAs($customer)
            ->post("/lich-hen/{$appointment->id}/huy", ['cancel_reason' => 'Bận việc đột xuất'])
            ->assertRedirect();

        $fresh = $appointment->fresh();
        $this->assertSame(AppointmentStatus::Cancelled, $fresh->status);
        $this->assertSame('Bận việc đột xuất', $fresh->cancel_reason);
    }

    public function test_customer_cannot_cancel_other_appointment_or_after_time(): void
    {
        $customer = $this->user();
        $other = Appointment::create([
            'type' => 'viewing', 'customer_id' => $this->user()->id,
            'contact_name' => 'B', 'contact_phone' => '0901234568',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);
        $this->actingAs($customer)
            ->post("/lich-hen/{$other->id}/huy", ['cancel_reason' => 'X'])
            ->assertForbidden();

        $passed = Appointment::create([
            'type' => 'viewing', 'customer_id' => $customer->id,
            'contact_name' => 'A', 'contact_phone' => '0901234567',
            'requested_at' => now()->subHour(), 'status' => 'pending',
        ]);
        $this->actingAs($customer)
            ->post("/lich-hen/{$passed->id}/huy", ['cancel_reason' => 'X'])
            ->assertRedirect()->assertSessionHasErrors('status');
        $this->assertSame(AppointmentStatus::Pending, $passed->fresh()->status);
    }

    public function test_staff_sees_scoped_list_and_admin_sees_all(): void
    {
        $admin = $this->user(UserRole::Admin);
        $sales = $this->user(UserRole::Sales);
        $other = $this->user(UserRole::Sales);

        $mine = Appointment::create([
            'type' => 'viewing', 'assigned_to' => $sales->id,
            'contact_name' => 'A', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);
        $unassigned = Appointment::create([
            'type' => 'viewing',
            'contact_name' => 'B', 'contact_phone' => '0901234568',
            'requested_at' => now()->addDays(2), 'status' => 'pending',
        ]);
        $foreign = Appointment::create([
            'type' => 'viewing', 'assigned_to' => $other->id,
            'contact_name' => 'C', 'contact_phone' => '0901234569',
            'requested_at' => now()->addDays(3), 'status' => 'pending',
        ]);

        $this->actingAs($sales)->get('/staff/appointments')
            ->assertOk()
            ->assertViewHas('appointments', fn ($page) => $page->count() === 2
                && $page->contains('id', $mine->id) && $page->contains('id', $unassigned->id)
                && ! $page->contains('id', $foreign->id));

        $this->actingAs($admin)->get('/staff/appointments')
            ->assertOk()
            ->assertViewHas('appointments', fn ($page) => $page->count() === 3);
        // actingAs persists across requests in the same test; clear guards
        // so this request is a true guest.
        $this->app['auth']->forgetGuards();
        $this->get('/staff/appointments')->assertRedirect('/dang-nhap');
    }

    public function test_admin_assigns_sales_and_staff_confirm_with_slot_rules(): void
    {
        $admin = $this->user(UserRole::Admin);
        $sales = $this->user(UserRole::Sales);
        $appointment = Appointment::create([
            'type' => 'viewing', 'customer_id' => $this->user()->id,
            'contact_name' => 'A', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/staff/appointments/{$appointment->id}/assign", ['assigned_to' => $sales->id])
            ->assertRedirect();
        $this->assertSame($sales->id, $appointment->fresh()->assigned_to);

        // Staff cannot confirm an appointment not assigned to them.
        $this->actingAs($this->user(UserRole::Sales))
            ->post("/staff/appointments/{$appointment->id}/confirm", ['confirmed_at' => now()->addDay()->format('Y-m-d H:i')])
            ->assertForbidden();

        // Sales confirms.
        $time = now()->addDay()->setTime(9, 0);
        $this->actingAs($sales)
            ->post("/staff/appointments/{$appointment->id}/confirm", ['confirmed_at' => $time->format('Y-m-d H:i')])
            ->assertRedirect();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);

        // Second appointment within 60 minutes for the same sales must be rejected.
        $second = Appointment::create([
            'type' => 'viewing', 'customer_id' => $this->user()->id,
            'contact_name' => 'D', 'contact_phone' => '0901234570',
            'requested_at' => now()->addDay(), 'status' => 'pending', 'assigned_to' => $sales->id,
        ]);
        $this->actingAs($sales)
            ->post("/staff/appointments/{$second->id}/confirm", ['confirmed_at' => $time->copy()->addMinutes(30)->format('Y-m-d H:i')])
            ->assertRedirect()->assertSessionHasErrors('assigned_to');
        $this->assertSame(AppointmentStatus::Pending, $second->fresh()->status);
    }

    public function test_confirm_rejects_past_time_and_invalid_assigned_staff(): void
    {
        $admin = $this->user(UserRole::Admin);
        $appointment = Appointment::create([
            'type' => 'viewing', 'customer_id' => $this->user()->id,
            'contact_name' => 'A', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);
        $this->actingAs($admin)
            ->post("/staff/appointments/{$appointment->id}/confirm", ['confirmed_at' => now()->subHour()->format('Y-m-d H:i')])
            ->assertRedirect()->assertSessionHasErrors();
        $this->assertSame(AppointmentStatus::Pending, $appointment->fresh()->status);
    }

    public function test_staff_cancels_with_reason_and_admin_any(): void
    {
        $admin = $this->user(UserRole::Admin);
        $sales = $this->user(UserRole::Sales);
        $appointment = Appointment::create([
            'type' => 'viewing', 'customer_id' => $this->user()->id, 'assigned_to' => $sales->id,
            'contact_name' => 'A', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);

        $this->actingAs($this->user(UserRole::Sales))
            ->post("/staff/appointments/{$appointment->id}/cancel", ['cancel_reason' => 'X'])
            ->assertForbidden();

        $this->actingAs($sales)
            ->post("/staff/appointments/{$appointment->id}/cancel", ['cancel_reason' => 'Khách xin dời'])
            ->assertRedirect();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);

        // Already cancelled cannot cancel again.
        $this->actingAs($admin)
            ->post("/staff/appointments/{$appointment->id}/cancel", ['cancel_reason' => 'X'])
            ->assertForbidden();
    }

    public function test_conclude_only_after_confirmed_time(): void
    {
        $sales = $this->user(UserRole::Sales);
        $appointment = Appointment::create([
            'type' => 'viewing', 'customer_id' => $this->user()->id, 'assigned_to' => $sales->id,
            'contact_name' => 'A', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);
        $time = now()->addDay()->setTime(9, 0);
        $this->actingAs($sales)
            ->post("/staff/appointments/{$appointment->id}/confirm", ['confirmed_at' => $time->format('Y-m-d H:i')])
            ->assertRedirect();

        // Before confirmed time: rejected.
        $this->travelTo($time->copy()->subMinutes(10));
        $this->actingAs($sales)
            ->post("/staff/appointments/{$appointment->id}/conclude", ['outcome' => 'completed'])
            ->assertRedirect()->assertSessionHasErrors('status');

        // After: allowed.
        $this->travelTo($time->copy()->addMinutes(10));
        $this->actingAs($sales)
            ->post("/staff/appointments/{$appointment->id}/conclude", ['outcome' => 'no_show'])
            ->assertRedirect();
        $this->assertSame(AppointmentStatus::NoShow, $appointment->fresh()->status);
    }
}
