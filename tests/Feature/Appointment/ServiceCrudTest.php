<?php

namespace Tests\Feature\Appointment;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function user(UserRole $role = UserRole::Customer): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Bảo dưỡng 40.000km',
            'type' => 'maintenance',
            'description' => 'Bảo dưỡng lớn theo mốc km.',
            'allow_booking' => '1',
            'is_visible' => '1',
        ], $overrides);
    }

    public function test_only_admin_can_manage_services(): void
    {
        $this->get('/admin/services')->assertRedirect('/dang-nhap');
        foreach ([UserRole::Customer, UserRole::Sales] as $role) {
            $this->actingAs($this->user($role));
            $this->get('/admin/services')->assertForbidden();
            $this->post('/admin/services', $this->payload())->assertForbidden();
        }
        $this->assertDatabaseCount('services', 0);
    }

    public function test_admin_can_create_update_and_list_services(): void
    {
        $admin = $this->user(UserRole::Admin);

        $this->actingAs($admin)->post('/admin/services', $this->payload())
            ->assertRedirect('/admin/services');
        $service = Service::where('name', 'Bảo dưỡng 40.000km')->firstOrFail();
        $this->assertSame('bao-duong-40000km', $service->slug);
        $this->assertTrue((bool) $service->allow_booking);

        $this->actingAs($admin)->post('/admin/services', $this->payload())
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)->put("/admin/services/{$service->id}", $this->payload([
            'name' => 'Bảo dưỡng 40.000km (cập nhật)',
            'allow_booking' => '0',
            'is_visible' => '0',
        ]))->assertRedirect('/admin/services');
        $fresh = $service->fresh();
        $this->assertSame('Bảo dưỡng 40.000km (cập nhật)', $fresh->name);
        $this->assertFalse((bool) $fresh->allow_booking);
        $this->assertFalse((bool) $fresh->is_visible);

        $this->actingAs($admin)->get('/admin/services')->assertOk()->assertSee('40.000km');
    }

    public function test_service_with_appointments_cannot_be_deleted(): void
    {
        $admin = $this->user(UserRole::Admin);
        $service = Service::create([
            'name' => 'Bảo hành chính hãng', 'slug' => 'bao-hanh',
            'type' => 'warranty', 'description' => 'Bảo hành 3 năm 100.000km.',
            'allow_booking' => false, 'is_visible' => true,
        ]);
        $appointment = Appointment::create([
            'type' => 'maintenance', 'service_id' => $service->id,
            'contact_name' => 'A', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDay(), 'status' => 'pending',
        ]);

        $this->actingAs($admin)->delete("/admin/services/{$service->id}")
            ->assertRedirect('/admin/services')->assertSessionHas('error');
        $this->assertModelExists($service);
        $this->assertModelExists($appointment);

        // Unreferenced service can be deleted.
        $other = Service::create([
            'name' => 'Sơn dặm', 'slug' => 'son-dam',
            'type' => 'maintenance', 'description' => 'Phủ ceramic.',
            'allow_booking' => false, 'is_visible' => false,
        ]);
        $this->actingAs($admin)->delete("/admin/services/{$other->id}")
            ->assertRedirect('/admin/services');
        $this->assertModelMissing($other);
    }

    public function test_public_services_page_shows_only_visible(): void
    {
        Service::create([
            'name' => 'Dịch vụ công khai', 'slug' => 'cong-khai',
            'type' => 'warranty', 'description' => 'Hiển thị.',
            'allow_booking' => false, 'is_visible' => true,
        ]);
        Service::create([
            'name' => 'Dịch vụ ẩn', 'slug' => 'an',
            'type' => 'maintenance', 'description' => 'Không hiển thị.',
            'allow_booking' => true, 'is_visible' => false,
        ]);

        $this->get('/dich-vu')->assertOk()
            ->assertSee('Dịch vụ công khai')
            ->assertDontSee('Dịch vụ ẩn');
    }
}
