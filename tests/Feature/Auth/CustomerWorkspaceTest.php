<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_workspace_requires_authentication(): void
    {
        $this->get('/tai-khoan')->assertRedirect(route('login'));
        $this->get('/tai-khoan/tin-nhan')->assertRedirect(route('login'));
    }

    public function test_customer_sees_only_own_profile_and_real_empty_workspace(): void
    {
        $user = User::factory()->create(['name' => '<script>profile</script>']);
        $other = User::factory()->create();
        $this->actingAs($user)->get('/tai-khoan')->assertOk()
            ->assertSee($user->email)->assertDontSee($other->email)
            ->assertSee('&lt;script&gt;profile&lt;/script&gt;', false)
            ->assertDontSee('<script>profile</script>', false)
            ->assertSee(route('customer.chat.index'))
            ->assertSee('Thông tin cá nhân');
        $this->get('/tai-khoan/tin-nhan')->assertOk()
            ->assertSee('Bạn chưa có yêu cầu tư vấn nào.')
            ->assertDontSee('Hội thoại minh họa')
            ->assertSee('name="_token"', false);
        $this->assertDatabaseCount('inquiries', 0);
        $this->assertDatabaseCount('messages', 0);
        $this->post('/tai-khoan/tin-nhan', ['content' => 'not sent'])->assertStatus(405);
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_staff_keep_existing_account_and_cannot_open_customer_preview(): void
    {
        foreach ([UserRole::Admin, UserRole::Sales] as $role) {
            $user = User::factory()->create();
            $user->forceFill(['role' => $role])->save();
            $this->actingAs($user)->get('/tai-khoan')->assertOk()
                ->assertSee($user->email)->assertDontSee('customer-shell');
            $this->get('/tai-khoan/tin-nhan')->assertForbidden();
        }
    }

    public function test_locked_customer_is_logged_out_before_accessing_chat(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->forceFill(['is_active' => false])->save();
        $this->get('/tai-khoan/tin-nhan')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
