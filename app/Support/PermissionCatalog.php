<?php

namespace App\Support;

final class PermissionCatalog
{
    public static function group(string $permission): string
    {
        return explode('.', $permission, 2)[0];
    }

    public static function groupLabel(string $group): string
    {
        return match ($group) {
            'admin' => 'Admin workspace',
            'dashboard' => 'Dashboard',
            'users' => 'User accounts',
            'roles' => 'Roles & permissions',
            'pages' => 'Pages',
            'news' => 'News',
            'galleries' => 'Galleries',
            'documents' => 'Documents & publications',
            'media' => 'Media library',
            'menus' => 'Navigation menus',
            'theme-layouts' => 'Header & footer',
            'settings' => 'Site settings & languages',
            'courses' => 'Training courses',
            'hero-slides' => 'Homepage slides',
            'school-leaders' => 'School leadership',
            'past-commandants' => 'Past commandants',
            'past-chief-instructors' => 'Past chief instructors',
            'contacts' => 'Contact messages',
            'redirects' => 'URL redirects',
            'audit' => 'Activity logs',
            default => ucwords(str_replace(['-', '_'], ' ', $group)),
        };
    }

    public static function label(string $permission): string
    {
        $action = explode('.', $permission, 2)[1] ?? $permission;

        return match ($action) {
            'access' => 'Open the admin workspace',
            'view' => 'View',
            'create' => 'Create',
            'update' => 'Edit',
            'delete' => 'Delete',
            'upload' => 'Upload files',
            'replace' => 'Replace files',
            'submit' => 'Submit for review',
            'approve' => 'Approve content',
            'publish' => 'Publish / unpublish',
            'request-changes' => 'Request changes',
            'assign-role' => 'Assign user roles',
            'reset-password' => 'Reset user passwords',
            'revisions.view' => 'View version history',
            'revisions.restore' => 'Restore a previous version',
            'categories.manage' => 'Manage categories',
            'manage' => 'Manage',
            default => ucwords(str_replace(['-', '_', '.'], ' ', $action)),
        };
    }

    /** @param list<string> $available
     * @return list<string>
     */
    public static function dependencies(string $permission, array $available): array
    {
        $required = $permission === 'admin.access' ? [] : ['admin.access'];
        $view = self::group($permission).'.view';
        if ($view !== $permission && in_array($view, $available, true)) {
            $required[] = $view;
        }

        // Creating accounts and resetting passwords also require their form routes.
        if ($permission === 'users.create') {
            $required[] = 'users.assign-role';
        }
        if ($permission === 'users.reset-password') {
            $required[] = 'users.update';
        }

        return array_values(array_intersect(array_unique($required), $available));
    }

    /** @param list<string> $selected
     * @param  list<string>  $available
     * @return list<string>
     */
    public static function withDependencies(array $selected, array $available): array
    {
        $selected = array_values(array_unique(array_intersect($selected, $available)));
        do {
            $before = count($selected);
            foreach ($selected as $name) {
                $selected = array_values(array_unique([...$selected, ...self::dependencies($name, $available)]));
            }
        } while (count($selected) !== $before);
        sort($selected);

        return $selected;
    }

    /** Removing a prerequisite also removes actions that depend on it.
     * @param  list<string>  $selected
     * @param  list<string>  $removed
     * @param  list<string>  $available
     * @return list<string>
     */
    public static function withoutDependants(array $selected, array $removed, array $available): array
    {
        $selected = array_values(array_diff(array_intersect($selected, $available), $removed));
        do {
            $before = count($selected);
            $selected = array_values(array_filter($selected, static fn (string $name): bool => array_diff(self::dependencies($name, $available), $selected) === []));
        } while (count($selected) !== $before);
        sort($selected);

        return $selected;
    }
}
