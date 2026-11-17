<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\Account\UpdateUserStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use DatabaseMigrations;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.$connection.database");
        if (! ($connection === 'sqlite' && $database === ':memory:')
            && ! ($connection === 'mysql' && config('database.connections.mysql.host') === '127.0.0.1'
                && ($database === 'toyota_showroom_test' || preg_match('/^toyota_showroom_tv4_test_\d+$/', $database)))) {
            throw new \RuntimeException('H02 regression requires a dedicated local test database.');
        }
    }

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

    private function inquiry(User $customer, User $sales): Inquiry
    {
        return Inquiry::create([
            'customer_id' => $customer->id, 'assigned_to' => $sales->id,
            'contact_name' => 'Khách thử nghiệm', 'contact_phone' => '0901234567',
            'content' => 'Tôi muốn xem xe tại showroom', 'status' => 'assigned',
        ]);
    }

    public function test_non_admins_cannot_access_any_user_management_endpoint(): void
    {
        $target = $this->user();
        $this->get('/admin/users')->assertRedirect('/dang-nhap');
        foreach ([UserRole::Customer, UserRole::Sales] as $role) {
            $this->actingAs($this->user($role));
            $this->get('/admin/users')->assertForbidden();
            $this->get('/admin/users/create')->assertForbidden();
            $this->post('/admin/users', [])->assertForbidden();
            $this->patch("/admin/users/{$target->id}/status", ['is_active' => false])->assertForbidden();
        }
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_admin_can_create_only_sales_without_changing_the_current_session(): void
    {
        $admin = $this->user(UserRole::Admin);
        $this->actingAs($admin)->get('/admin/users/create')->assertOk();
        $this->post('/admin/users', [
            'name' => 'Nhân viên mới', 'email' => ' SALES.NEW@EXAMPLE.TEST ', 'phone' => '0901234567',
            'password' => 'DemoPassword123', 'password_confirmation' => 'DemoPassword123',
            'role' => 'admin', 'is_active' => false,
        ])->assertRedirect('/admin/users');
        $sales = User::where('email', 'sales.new@example.test')->firstOrFail();
        $this->assertSame(UserRole::Sales, $sales->role);
        $this->assertTrue($sales->is_active);
        $this->assertTrue(Hash::check('DemoPassword123', $sales->password));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_create_sales_validates_duplicate_email_phone_and_password(): void
    {
        $admin = $this->user(UserRole::Admin);
        $this->actingAs($admin)->postJson('/admin/users', [
            'name' => '', 'email' => $admin->email, 'phone' => '+84901234567',
            'password' => 'short', 'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'phone', 'password']);
        $this->assertDatabaseCount('users', 1);
        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_list_can_search_filter_and_paginate_without_exposing_secrets(): void
    {
        $admin = $this->user(UserRole::Admin);
        for ($i = 0; $i < 17; $i++) {
            $user = $this->user(UserRole::Sales);
            $user->update(['name' => 'Staff Demo '.$i]);
        }
        $this->actingAs($admin)->get('/admin/users?role=sales&q=Staff')
            ->assertOk()->assertViewHas('users', fn ($users) => $users->total() === 17 && $users->count() === 15)
            ->assertSee('page=2', false)->assertDontSee($admin->password);
        $this->get('/admin/users?q=Staff%20Demo%200')->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 1);
        $this->get('/admin/users?q=0')->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() < 18);
        $this->get('/admin/users?q=khongco123xyz')->assertOk()->assertSee('Không có tài khoản phù hợp.');
        $this->getJson('/admin/users?role=superuser')->assertUnprocessable();
    }

    public function test_lock_bumps_only_related_versions_once_and_preserves_assignments(): void
    {
        $admin = $this->user(UserRole::Admin);
        $sales = $this->user(UserRole::Sales);
        $customer = $this->user();
        $related = $this->inquiry($customer, $sales);
        $unrelated = $this->inquiry($this->user(), $this->user(UserRole::Sales));
        $this->actingAs($admin)->patch("/admin/users/{$sales->id}/status", ['is_active' => false])
            ->assertRedirect('/admin/users');
        $this->assertFalse($sales->fresh()->is_active);
        $this->assertSame(2, $related->fresh()->access_version);
        $this->assertSame($sales->id, $related->fresh()->assigned_to);
        $this->assertSame(1, $unrelated->fresh()->access_version);
        $this->patch("/admin/users/{$sales->id}/status", ['is_active' => false])->assertRedirect();
        $this->assertSame(2, $related->fresh()->access_version);
        $this->patch("/admin/users/{$sales->id}/status", ['is_active' => true])->assertRedirect();
        $this->assertTrue($sales->fresh()->is_active);
        $this->assertSame(2, $related->fresh()->access_version);
        $this->patch("/admin/users/{$customer->id}/status", ['is_active' => false])->assertRedirect();
        $this->assertSame(3, $related->fresh()->access_version);
    }

    public function test_admin_accounts_and_unvalidated_status_cannot_be_changed(): void
    {
        $admin = $this->user(UserRole::Admin);
        $otherAdmin = $this->user(UserRole::Admin);
        $customer = $this->user();
        $this->actingAs($admin)->patch("/admin/users/{$admin->id}/status", ['is_active' => false])->assertForbidden();
        $this->patch("/admin/users/{$otherAdmin->id}/status", ['is_active' => false])->assertForbidden();
        $this->patchJson("/admin/users/{$customer->id}/status", [])->assertUnprocessable();
        $this->patchJson("/admin/users/{$customer->id}/status", ['is_active' => 'false'])->assertUnprocessable();
        $this->delete("/admin/users/{$customer->id}")->assertNotFound();
        $this->assertTrue($customer->fresh()->is_active);
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertTrue($otherAdmin->fresh()->is_active);
    }

    public function test_service_rechecks_actor_and_target_roles_inside_the_transaction(): void
    {
        $admin = $this->user(UserRole::Admin);
        $customer = $this->user();
        User::whereKey($admin->id)->update(['is_active' => false]);
        $this->expectException(AuthorizationException::class);
        app(UpdateUserStatus::class)->handle($admin, $customer, false);
    }

    public function test_version_failure_rolls_back_user_lock_too(): void
    {
        $admin = $this->user(UserRole::Admin);
        $sales = $this->user(UserRole::Sales);
        $inquiry = $this->inquiry($this->user(), $sales);
        DB::listen(function ($event): void {
            if (str_starts_with(strtolower($event->sql), 'update') && str_contains($event->sql, 'inquiries')) {
                throw new \RuntimeException('Simulated version write failure');
            }
        });
        try {
            app(UpdateUserStatus::class)->handle($admin, $sales, false);
            $this->fail('The simulated write failure must propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated version write failure', $exception->getMessage());
        }
        $this->assertTrue($sales->fresh()->is_active);
        $this->assertSame(1, $inquiry->fresh()->access_version);
    }

    public function test_gate_releases_after_rollback_and_retries_preserve_h02_idempotency(): void
    {
        $admin = $this->user(UserRole::Admin);
        $sales = $this->user(UserRole::Sales);
        $inquiry = $this->inquiry($this->user(), $sales);
        $armed = true;
        DB::listen(function ($query) use (&$armed): void {
            if ($armed && str_starts_with(strtolower($query->sql), 'update') && str_contains($query->sql, 'inquiries')) {
                $armed = false;
                throw new \RuntimeException('Injected revocation rollback');
            }
        });
        try {
            app(UpdateUserStatus::class)->handle($admin, $sales, false);
            $this->fail('Failure must propagate');
        } catch (\RuntimeException $e) {
            $this->assertSame('Injected revocation rollback', $e->getMessage());
        }
        $this->assertTrue($sales->fresh()->is_active);
        $this->assertSame(1, $inquiry->fresh()->access_version);
        app(UpdateUserStatus::class)->handle($admin, $sales, false);
        app(UpdateUserStatus::class)->handle($admin, $sales, false);
        $this->assertSame(2, $inquiry->fresh()->access_version);
        $this->assertFalse($sales->fresh()->is_active);
        app(UpdateUserStatus::class)->handle($admin, $sales, true);
        $this->assertSame(2, $inquiry->fresh()->access_version);
    }
}
