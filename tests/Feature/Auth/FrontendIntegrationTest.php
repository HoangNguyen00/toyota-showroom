<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function visibleCar(): Car
    {
        $category = CarCategory::factory()->create(['is_visible' => true]);

        return Car::factory()->create(['category_id' => $category->id, 'is_visible' => true]);
    }

    public function test_guest_can_reach_auth_from_home_catalog_and_detail(): void
    {
        $car = $this->visibleCar();
        foreach (['/', '/xe', "/xe/{$car->id}"] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('href="'.route('login').'"', false)
                ->assertDontSee('data-preview="Đăng nhập"', false);
        }
        $this->get('/dang-nhap')->assertOk()->assertSee(route('register'));
        $this->get('/dang-ky')->assertOk()->assertSee(route('login'));
    }

    public function test_each_role_can_browse_frontend_with_account_and_logout_links(): void
    {
        $car = $this->visibleCar();
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create();
            $user->forceFill(['role' => $role])->save();
            $this->actingAs($user);
            foreach (['/', '/xe', '/xe/1'] as $url) {
                $this->get($url)->assertOk()
                    ->assertSee('href="'.route('account').'"', false)
                    ->assertSee('action="'.route('logout').'"', false)
                    ->assertSee('name="_token"', false)
                    ->assertDontSee('href="'.route('login').'"', false);
            }
            $this->get('/tai-khoan')->assertOk()->assertSee($user->email);
        }
    }

    public function test_login_then_catalog_and_logout_returns_to_guest_navigation(): void
    {
        $user = User::factory()->create();
        $this->post('/dang-nhap', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/tai-khoan');
        $this->get('/xe')->assertOk()->assertSee(route('account'));
        $this->post('/dang-xuat')->assertRedirect('/dang-nhap');
        $this->get('/')->assertOk()->assertSee(route('login'))->assertSee('toyota-headlight.jpg');
        $this->assertGuest();
    }

    public function test_logout_rejects_missing_csrf_token_outside_test_bypass(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['_token' => 'integration-csrf-token']);
        $this->app['env'] = 'local';
        $this->post('/dang-xuat')->assertStatus(419);
        $this->assertAuthenticatedAs($user);
        $this->post('/dang-xuat', ['_token' => 'integration-csrf-token'])
            ->assertRedirect('/dang-nhap');
        $this->assertGuest();
    }
}
