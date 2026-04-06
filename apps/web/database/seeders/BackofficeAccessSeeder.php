<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class BackofficeAccessSeeder extends Seeder
{
    use WithoutModelEvents;

    private const RESERVED_PLACEHOLDER = 'CHANGE_ME';

    /**
     * @var list<string>
     */
    private const PERMISSIONS = [
        'dashboard.view',
        'reports.view',
        'leads.view',
        'leads.note.create',
        'leads.status.update',
        'users.manage',
        'horizon.view',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'admin' => self::PERMISSIONS,
        'manager' => [
            'dashboard.view',
            'reports.view',
            'leads.view',
            'leads.note.create',
            'leads.status.update',
        ],
    ];

    public function run(): void
    {
        $guardName = (string) config('auth.defaults.guard', 'web');

        $this->seedPermissions($guardName);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seedRoles($guardName);
        $this->seedUsers();
    }

    private function seedPermissions(string $guardName): void
    {
        foreach (self::PERMISSIONS as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guardName,
            ]);
        }
    }

    private function seedRoles(string $guardName): void
    {
        foreach (self::ROLE_PERMISSIONS as $roleName => $permissionNames) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guardName,
            ]);

            $role->syncPermissions($permissionNames);
        }
    }

    private function seedUsers(): void
    {
        foreach (config('backoffice.bootstrap.users', []) as $roleName => $credentials) {
            $this->seedUser((string) $roleName, $credentials);
        }
    }

    /**
     * @param  array{name?: mixed, email?: mixed, password?: mixed}  $credentials
     */
    private function seedUser(string $roleName, array $credentials): User
    {
        $name = $this->requiredCredential($credentials, 'name', $roleName);
        $email = $this->requiredCredential($credentials, 'email', $roleName);
        $password = $this->requiredCredential($credentials, 'password', $roleName);

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
            ],
        );

        $user->syncRoles([$roleName]);

        return $user;
    }

    /**
     * @param  array{name?: mixed, email?: mixed, password?: mixed}  $credentials
     */
    private function requiredCredential(array $credentials, string $key, string $roleName): string
    {
        $value = trim((string) ($credentials[$key] ?? ''));

        if ($value === '') {
            throw new RuntimeException(sprintf('Backoffice %s %s credential is not configured.', $roleName, $key));
        }

        if (config('app.env') === 'production' && $value === self::RESERVED_PLACEHOLDER) {
            throw new RuntimeException(sprintf(
                'Backoffice %s %s credential still uses the production placeholder.',
                $roleName,
                $key,
            ));
        }

        return $value;
    }
}
