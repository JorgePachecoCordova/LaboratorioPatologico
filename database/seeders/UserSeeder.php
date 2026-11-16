<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $name = trim((string) config('initial_admin.name'));
        $username = trim((string) config('initial_admin.username'));
        $email = trim((string) config('initial_admin.email'));
        $password = (string) config('initial_admin.password');

        if ($username === '' || ($password === '' && !User::query()->where('username', $username)->exists())) {
            throw new RuntimeException(
                'INITIAL_ADMIN_USERNAME e INITIAL_ADMIN_PASSWORD no pueden estar vacíos.'
            );
        }

        $permissions = collect([
            'access dashboard',
            'manage requests',
            'manage results',
            'view results',
            'manage laboratory workflow',
            'manage cytology reports',
            'manage anatomopathology reports',
            'manage configuration',
        ])->mapWithKeys(function (string $name): array {
            $permission = Permission::findOrCreate($name, 'web');

            return [$name => $permission];
        });

        $administratorRole = Role::findOrCreate('administrator', 'web');
        $administratorRole->syncPermissions($permissions->values());

        $userRole = Role::findOrCreate('user', 'web');
        $userRole->syncPermissions([]);
        Role::findOrCreate('reception', 'web')->syncPermissions($permissions->only([
            'access dashboard', 'manage requests', 'view results',
        ])->values());
        Role::findOrCreate('result_reader', 'web')->syncPermissions($permissions->only([
            'access dashboard', 'view results', 'manage results',
        ])->values());

        Role::findOrCreate('laboratory_office', 'web')->syncPermissions($permissions->only([
            'access dashboard', 'manage requests', 'view results',
        ])->values());
        Role::findOrCreate('cytology_office', 'web')->syncPermissions($permissions->only([
            'access dashboard', 'manage cytology reports',
        ])->values());
        Role::findOrCreate('anatomopathology_office', 'web')->syncPermissions($permissions->only([
            'access dashboard', 'manage anatomopathology reports',
        ])->values());

        $administrator = User::query()->firstOrCreate(
            ['username' => $username],
            [
                'name' => $name !== '' ? $name : 'Administrador de Patología',
                'email' => $email !== '' ? $email : null,
                'password' => Hash::make($password),
                'role' => 'administrator',
                'is_active' => true,
            ]
        );

        if ($administrator->wasRecentlyCreated) $administrator->syncRoles($administratorRole);

        User::query()->whereKeyNot($administrator->id)->doesntHave('roles')->each(
            fn (User $user): mixed => $user->syncRoles($userRole)
        );
    }
}
