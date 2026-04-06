<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class BackofficePermissions
{
    /**
     * @var list<string>
     */
    public const ALL = [
        'dashboard.view',
        'reports.view',
        'clicks.view',
        'visits.view',
        'touches.view',
        'leads.view',
        'leads.note.create',
        'leads.status.update',
        'users.manage',
        'horizon.view',
    ];

    /**
     * @var array<string, list<string>>
     */
    public const ROLE_PERMISSIONS = [
        'admin' => self::ALL,
        'manager' => [
            'dashboard.view',
            'reports.view',
            'clicks.view',
            'visits.view',
            'touches.view',
            'leads.view',
            'leads.note.create',
            'leads.status.update',
        ],
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::ALL;
    }

    /**
     * @return list<string>
     */
    public static function rolePermissions(string $roleName): array
    {
        return self::ROLE_PERMISSIONS[$roleName] ?? [];
    }

    /**
     * @return list<string>
     */
    public static function permissions(): array
    {
        $permissions = session('backoffice_permissions', []);

        if (! is_array($permissions)) {
            $permissions = [];
        }

        $permissions = array_values(array_unique(array_map('strval', $permissions)));

        if ($permissions !== []) {
            return $permissions;
        }

        $roleName = trim((string) session('backoffice_role_name', ''));

        if ($roleName !== '') {
            return self::rolePermissions($roleName);
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $roleName = $user->getRoleNames()->first() ?? '';

        return $roleName !== '' ? self::rolePermissions($roleName) : [];
    }

    public static function roleName(): string
    {
        $roleName = trim((string) session('backoffice_role_name', ''));

        if ($roleName !== '') {
            return $roleName;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return 'Користувач';
        }

        return $user->getRoleNames()->first() ?? 'Користувач';
    }

    public static function can(string $permission): bool
    {
        return in_array($permission, self::permissions(), true);
    }

    /**
     * @param  list<string>  $permissions
     */
    public static function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can((string) $permission)) {
                return true;
            }
        }

        return false;
    }
}
