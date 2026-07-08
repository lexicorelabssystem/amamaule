<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'web';

        $permissions = [
            // Users
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Roles
            'roles.manage',

            // Artists
            'artists.view_own',
            'artists.view_any',
            'artists.create',
            'artists.edit_own',
            'artists.edit_any',
            'artists.delete',
            'artists.review',
            'artists.approve',
            'artists.reject',
            'artists.archive',

            // Disciplines & territories
            'disciplines.manage',
            'territories.manage',

            // Activities
            'activities.create_own',
            'activities.edit_own',
            'activities.view_any',
            'activities.review',
            'activities.approve',
            'activities.publish',
            'activities.archive',

            // Proposals
            'proposals.create_own',
            'proposals.edit_own',
            'proposals.view_any',
            'proposals.review',
            'proposals.approve',
            'proposals.reject',
            'proposals.archive',

            // Internal comments
            'comments.view_internal',
            'comments.create_internal',

            // WordPress publications
            'wordpress.publish',
            'wordpress.update',
            'wordpress.unpublish',

            // Imports
            'imports.create',

            // Exports
            'exports.create',

            // Audit
            'audit.view',

            // Settings
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        // Refresh the permission cache so roles can be assigned safely.
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roleArtist = Role::findOrCreate('artista', $guard);
        $roleArtist->syncPermissions([
            'artists.view_own',
            'artists.edit_own',
            'activities.create_own',
            'activities.edit_own',
            'proposals.create_own',
            'proposals.edit_own',
        ]);

        $roleOperativo = Role::findOrCreate('operativo', $guard);
        $roleOperativo->syncPermissions([
            'users.view',
            'artists.view_any',
            'artists.create',
            'artists.edit_any',
            'artists.delete',
            'artists.review',
            'artists.approve',
            'artists.reject',
            'activities.view_any',
            'activities.review',
            'activities.approve',
            'proposals.view_any',
            'proposals.review',
            'proposals.approve',
            'proposals.reject',
            'comments.view_internal',
            'comments.create_internal',
            'exports.create',
        ]);

        $roleRevisor = Role::findOrCreate('revisor', $guard);
        $roleRevisor->syncPermissions([
            'artists.view_any',
            'artists.create',
            'artists.edit_any',
            'artists.review',
            'artists.approve',
            'artists.reject',
            'activities.view_any',
            'activities.review',
            'activities.approve',
            'proposals.view_any',
            'proposals.review',
            'proposals.approve',
            'proposals.reject',
            'comments.view_internal',
            'comments.create_internal',
            'exports.create',
        ]);

        $roleComunicaciones = Role::findOrCreate('comunicaciones', $guard);
        $roleComunicaciones->syncPermissions([
            'artists.view_any',
            'activities.view_any',
            'activities.publish',
            'proposals.view_any',
            'wordpress.publish',
            'wordpress.update',
            'wordpress.unpublish',
            'exports.create',
            'comments.view_internal',
            'comments.create_internal',
        ]);

        $roleAdmin = Role::findOrCreate('admin', $guard);
        $roleAdmin->syncPermissions(Permission::all()->pluck('name')->toArray());

        $roleSuperAdmin = Role::findOrCreate('super_admin', $guard);
        $roleSuperAdmin->syncPermissions(Permission::all()->pluck('name')->toArray());

        $roleSoporte = Role::findOrCreate('soporte', $guard);
        $roleSoporte->syncPermissions([
            'users.view',
            'artists.view_any',
        ]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
