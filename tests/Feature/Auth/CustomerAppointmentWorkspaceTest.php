<?php

namespace Tests\Feature\Auth;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAppointmentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function appointment(User $user, array $extra = []): Appointment
    {
        return Appointment::create(array_merge([
            'customer_id' => $user->id, 'type' => AppointmentType::Viewing,
            'contact_name' => $user->name, 'contact_phone' => '0901234567',
            'requested_at' => now()->addDays(2), 'status' => AppointmentStatus::Pending,
        ], $extra));
    }

    public function test_account_and_list_show_only_owned_appointments_with_correct_time(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $mine = $this->appointment($user, ['requested_at' => now()->subDays(1), 'confirmed_at' => now()->addDays(3), 'status' => AppointmentStatus::Confirmed]);
        $foreign = $this->appointment($other, ['customer_car_info' => 'PRIVATE-OTHER-CAR']);
        $this->actingAs($user)->get('/tai-khoan')->assertOk()
            ->assertSee('Lịch hẹn sắp tới')->assertSee($mine->confirmed_at->format('H:i d/m/Y'))
            ->assertSee(route('customer.appointments.index'));
        $this->get('/lich-hen')->assertOk()->assertSee('customer-shell')
            ->assertSee('LỊCH HẸN #'.$mine->id)->assertDontSee('PRIVATE-OTHER-CAR')
            ->assertSee(route('customer.appointments.cancel', $mine));
        $this->post(route('customer.appointments.cancel', $foreign), ['cancel_reason' => 'not mine'])->assertForbidden();
        $this->assertSame(AppointmentStatus::Pending, $foreign->fresh()->status);
    }

    public function test_customer_can_cancel_owned_upcoming_appointment_from_workspace(): void
    {
        $user = User::factory()->create();
        $mine = $this->appointment($user);
        $this->actingAs($user)->from('/lich-hen')->post(route('customer.appointments.cancel', $mine), ['cancel_reason' => 'Thay đổi kế hoạch'])->assertRedirect('/lich-hen');
        $this->assertSame(AppointmentStatus::Cancelled, $mine->fresh()->status);
        $this->get('/lich-hen')->assertOk()->assertSee('Thay đổi kế hoạch')
            ->assertDontSee('Xác nhận hủy lịch');
        $this->get('/tai-khoan')->assertOk()->assertSee('Chưa có lịch hẹn sắp tới');
    }
}
