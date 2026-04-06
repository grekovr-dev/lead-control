<?php

declare(strict_types=1);

namespace Tests\Unit\App\Providers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class HorizonServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allows_users_with_the_horizon_permission(): void
    {
        $this->seedHorizonPermission();

        $admin = $this->createUserWithRole('admin');

        $this->assertTrue(Gate::forUser($admin)->allows('viewHorizon'));
    }

    public function test_it_denies_users_without_the_horizon_permission(): void
    {
        $this->seedHorizonPermission();

        $manager = $this->createUserWithRole('manager');

        $this->assertFalse(Gate::forUser($manager)->allows('viewHorizon'));
    }

    public function test_it_uses_the_gate_even_in_local_environment(): void
    {
        $this->seedHorizonPermission();

        $manager = $this->createUserWithRole('manager');

        $request = Request::create('/horizon');
        $request->setUserResolver(static fn (): User => $manager);

        $this->assertFalse(Horizon::check($request));
    }

    private function seedHorizonPermission(): void
    {
        $permission = Permission::query()->create([
            'name' => 'horizon.view',
            'guard_name' => 'web',
        ]);

        foreach (['admin', 'manager'] as $roleName) {
            Role::query()->create([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        Role::query()->where('name', 'admin')->firstOrFail()->syncPermissions([$permission]);
    }

    private function createUserWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $user->syncRoles([$roleName]);

        return $user;
    }
}
