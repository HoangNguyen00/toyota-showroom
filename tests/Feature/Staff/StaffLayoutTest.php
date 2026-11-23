<?php

namespace Tests\Feature\Staff;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\Inquiry;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Layout nghiệp vụ chung (layouts/staff) và khối "Xe khách chọn" cho sales/admin.
 * Chỉ kiểm tra trình bày; quyền vẫn do middleware/Policy kiểm ở các test module.
 */
class StaffLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function user(UserRole $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    /** Hai chiếc cùng tên, khác mã và màu. */
    private function twins(): array
    {
        $black = Car::factory()->create(['name' => 'Toyota Camry', 'code' => 'CAMRY-TWIN-01', 'color' => 'Đen']);
        $white = Car::factory()->create(['name' => 'Toyota Camry', 'code' => 'CAMRY-TWIN-02', 'color' => 'Trắng ngọc trai']);
        CarImage::create(['car_id' => $black->id, 'path' => 'assets/products/toyota_vios.png', 'sort_order' => 1, 'is_primary' => true]);

        return [$black, $white];
    }

    private function inquiry(?Car $car, ?User $customer, ?User $sales, string $status = 'assigned'): Inquiry
    {
        return Inquiry::create([
            'customer_id' => $customer?->id, 'assigned_to' => $sales?->id, 'car_id' => $car?->id,
            'contact_name' => 'Khách mẫu', 'contact_phone' => '0900000000',
            'content' => 'Tư vấn xe', 'status' => $status, 'internal_note' => 'PRIVATE-NOTE',
        ]);
    }

    public function test_sales_and_admin_share_the_layout_with_role_menus(): void
    {
        $sales = $this->user(UserRole::Sales);
        $this->actingAs($sales)->get('/tai-khoan')->assertOk()
            ->assertSee('class="staff-page"', false)
            ->assertSee(route('staff.inquiries.index'), false)
            ->assertSee(route('staff.appointments.index'), false)
            ->assertDontSee(route('admin.users.index'), false)
            ->assertDontSee(route('admin.cars.index'), false);

        $admin = $this->user(UserRole::Admin);
        $response = $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('class="staff-page"', false);
        foreach (['admin.inquiries.index', 'staff.appointments.index', 'admin.cars.index', 'admin.car-categories.index', 'admin.services.index', 'admin.users.index'] as $route) {
            $response->assertSee(route($route), false);
        }
        $response->assertSee('aria-current="page"', false);
    }

    public function test_inquiry_list_and_detail_show_the_exact_car_code_for_same_name_cars(): void
    {
        [$black, $white] = $this->twins();
        $customer = $this->user(UserRole::Customer);
        $sales = $this->user(UserRole::Sales);
        $first = $this->inquiry($black, $customer, $sales);
        $second = $this->inquiry($white, $customer, $sales);
        $general = $this->inquiry(null, $customer, $sales);

        $this->actingAs($sales)->get('/staff/inquiries')->assertOk()
            ->assertSee('CAMRY-TWIN-01')->assertSee('CAMRY-TWIN-02')->assertSee('Chưa chọn xe');

        $this->get('/staff/inquiries/'.$first->id)->assertOk()
            ->assertSee('Xe khách chọn')->assertSee('CAMRY-TWIN-01')->assertSee('Đen')
            ->assertSee('assets/products/toyota_vios.png', false)
            ->assertDontSee('CAMRY-TWIN-02')
            ->assertSee('PRIVATE-NOTE')->assertSee('data-inquiry-chat', false)->assertSee('data-chat-scroll', false);
        $this->get('/staff/inquiries/'.$second->id)->assertOk()
            ->assertSee('CAMRY-TWIN-02')->assertSee('Trắng ngọc trai')->assertDontSee('CAMRY-TWIN-01');
        $this->get('/staff/inquiries/'.$general->id)->assertOk()->assertSee('Chưa chọn xe');

        // Bản JSON giữ allowlist cũ: xe chỉ có id/name.
        $this->getJson('/staff/inquiries/'.$first->id)->assertOk()
            ->assertJsonPath('car', ['id' => $black->id, 'name' => 'Toyota Camry']);
    }

    public function test_customer_html_inquiry_pages_keep_the_account_layout_without_internal_note(): void
    {
        [$black] = $this->twins();
        $customer = $this->user(UserRole::Customer);
        $own = $this->inquiry($black, $customer, $this->user(UserRole::Sales));

        $this->actingAs($customer)->get('/yeu-cau')->assertOk()
            ->assertDontSee('class="staff-page"', false)->assertSee('CAMRY-TWIN-01');
        $this->get('/yeu-cau/'.$own->id)->assertOk()
            ->assertDontSee('class="staff-page"', false)->assertSee('CAMRY-TWIN-01')
            ->assertDontSee('PRIVATE-NOTE')->assertDontSee('0900000000');
    }

    public function test_appointment_list_shows_car_code_for_viewing_and_service_first_for_maintenance(): void
    {
        [$black, $white] = $this->twins();
        $admin = $this->user(UserRole::Admin);
        $service = Service::create(['name' => 'Bảo dưỡng 10.000km', 'slug' => 'bao-duong-10000km', 'type' => 'maintenance', 'description' => 'Kiểm tra định kỳ', 'allow_booking' => true, 'is_visible' => true]);
        foreach ([$black, $white] as $i => $car) {
            Appointment::create(['type' => 'viewing', 'car_id' => $car->id, 'contact_name' => 'Khách '.$i, 'contact_phone' => '090000000'.$i,
                'requested_at' => now()->addDays(2 + $i), 'status' => 'pending']);
        }
        Appointment::create(['type' => 'maintenance', 'service_id' => $service->id, 'customer_car_info' => 'Vios 2022, 30.000 km',
            'contact_name' => 'Khách bảo dưỡng', 'contact_phone' => '0900000009', 'requested_at' => now()->addDays(5), 'status' => 'pending']);

        $this->actingAs($admin)->get('/staff/appointments')->assertOk()
            ->assertSee('CAMRY-TWIN-01')->assertSee('CAMRY-TWIN-02')
            ->assertSee('Giờ mong muốn')->assertSee('Giờ xác nhận')->assertSee('Người phụ trách')
            ->assertSee('Bảo dưỡng 10.000km')->assertSee('Xe khách khai báo')->assertSee('Vios 2022, 30.000 km');
    }
}
