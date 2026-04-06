<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class BackofficeSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_backoffice_login_page_in_ukrainian(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeText([
                'Вхід у бекофіс',
                'Авторизація',
                'Електронна пошта',
                'Пароль',
                'Увійти',
            ]);
    }

    public function test_it_redirects_authenticated_users_away_from_the_login_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_it_authenticates_users_and_redirects_to_the_backoffice_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_it_rejects_invalid_credentials_with_a_ukrainian_error(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_it_logs_out_the_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
