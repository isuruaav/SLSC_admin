<?php

namespace App\Support;

final class RolePresets
{
    /** @return list<string> */
    public static function permissions(): array
    {
        return [
            'admin.access', 'dashboard.view', 'users.view',
            'users.create', 'users.update', 'users.delete',
            'users.assign-role', 'users.reset-password', 'roles.manage',
            'pages.view', 'pages.create', 'pages.update',
            'pages.delete', 'pages.submit', 'pages.approve',
            'pages.publish', 'pages.revisions.view', 'pages.revisions.restore',
            'news.view', 'news.create', 'news.update',
            'news.delete', 'news.submit', 'news.request-changes',
            'news.approve', 'news.publish', 'news.categories.manage',
            'galleries.view', 'galleries.create', 'galleries.update',
            'galleries.delete', 'galleries.publish', 'documents.view',
            'documents.create', 'documents.update', 'documents.delete',
            'documents.publish', 'documents.categories.manage', 'media.view',
            'media.upload', 'media.update', 'media.replace',
            'media.delete', 'menus.manage', 'theme-layouts.manage',
            'courses.view', 'courses.create', 'courses.update', 'courses.delete', 'courses.publish', 'courses.categories.manage',
            'settings.manage', 'hero-slides.view', 'hero-slides.create',
            'hero-slides.update', 'hero-slides.delete', 'school-leaders.view',
            'school-leaders.update', 'past-commandants.view', 'past-commandants.create',
            'past-commandants.update', 'past-commandants.delete', 'past-chief-instructors.view',
            'past-chief-instructors.create', 'past-chief-instructors.update', 'past-chief-instructors.delete',
            'organizations.view', 'organizations.create', 'organizations.update',
            'organizations.delete', 'organizations.publish', 'organizations.import',
            'organizations.categories.manage',
            'contacts.manage', 'redirects.manage', 'audit.view',
        ];
    }

    /** @return array<string, array{description: string, icon: string, permissions: list<string>}> */
    public static function all(): array
    {
        return [
            'Super Administrator' => [
                'description' => 'Full system access, including users, roles and permissions. Keep this role for the site owner.',
                'icon' => 'fa-solid fa-user-shield',
                'permissions' => self::permissions(),
            ],
            'Site Administrator' => [
                'description' => 'Runs the website, manages content, navigation, site languages, settings and everyday user accounts.',
                'icon' => 'fa-solid fa-user-gear',
                'permissions' => [
                    'admin.access', 'dashboard.view', 'users.view',
                    'users.create', 'users.update', 'users.assign-role',
                    'users.reset-password', 'pages.view', 'pages.create',
                    'pages.update', 'pages.delete', 'pages.submit',
                    'pages.approve', 'pages.publish', 'pages.revisions.view',
                    'pages.revisions.restore', 'news.view', 'news.create',
                    'news.update', 'news.delete', 'news.submit',
                    'news.request-changes', 'news.approve', 'news.publish',
                    'news.categories.manage', 'galleries.view', 'galleries.create',
                    'galleries.update', 'galleries.delete', 'galleries.publish',
                    'documents.view', 'documents.create', 'documents.update',
                    'documents.delete', 'documents.publish', 'documents.categories.manage',
                    'media.view', 'media.upload', 'media.update',
                    'media.replace', 'media.delete', 'menus.manage',
                    'theme-layouts.manage', 'courses.view', 'courses.create', 'courses.update', 'courses.delete', 'courses.publish', 'courses.categories.manage',
                    'settings.manage', 'hero-slides.view',
                    'hero-slides.create', 'hero-slides.update', 'hero-slides.delete',
                    'school-leaders.view', 'school-leaders.update', 'past-commandants.view',
                    'past-commandants.create', 'past-commandants.update', 'past-commandants.delete',
                    'past-chief-instructors.view', 'past-chief-instructors.create', 'past-chief-instructors.update',
                    'past-chief-instructors.delete',
                    'organizations.view', 'organizations.create', 'organizations.update',
                    'organizations.delete', 'organizations.publish', 'organizations.import',
                    'organizations.categories.manage',
                    'contacts.manage', 'redirects.manage',
                    'audit.view',
                ],
            ],
            'Training Manager' => [
                'description' => 'Manages course information, bilingual qualifications and course availability.',
                'icon' => 'fa-solid fa-graduation-cap',
                'permissions' => ['admin.access', 'dashboard.view', 'courses.view', 'courses.create', 'courses.update', 'courses.delete', 'courses.publish', 'courses.categories.manage'],
            ],
            'Publisher' => [
                'description' => 'Reviews and publishes pages, news, galleries and documents.',
                'icon' => 'fa-solid fa-circle-check',
                'permissions' => [
                    'admin.access', 'dashboard.view', 'pages.view',
                    'pages.create', 'pages.update', 'pages.submit',
                    'pages.approve', 'pages.publish', 'pages.revisions.view',
                    'pages.revisions.restore', 'news.view', 'news.create',
                    'news.update', 'news.submit', 'news.request-changes',
                    'news.approve', 'news.publish', 'news.categories.manage',
                    'galleries.view', 'galleries.create', 'galleries.update',
                    'galleries.publish', 'documents.view', 'documents.create',
                    'documents.update', 'documents.publish', 'documents.categories.manage',
                    'media.view', 'media.upload', 'media.update',
                    'organizations.view', 'organizations.create', 'organizations.update',
                    'organizations.publish', 'organizations.categories.manage',
                ],
            ],
            'Content Editor' => [
                'description' => 'Prepares pages and news, uploads images and submits content for review.',
                'icon' => 'fa-solid fa-pen-to-square',
                'permissions' => [
                    'admin.access', 'dashboard.view', 'pages.view',
                    'pages.create', 'pages.update', 'pages.submit',
                    'pages.revisions.view', 'pages.revisions.restore', 'news.view',
                    'news.create', 'news.update', 'news.submit',
                    'galleries.view', 'galleries.create', 'galleries.update',
                    'documents.view', 'documents.create', 'documents.update',
                    'media.view', 'media.upload', 'media.update',
                    'organizations.view', 'organizations.create', 'organizations.update',
                ],
            ],
            'Media Operator' => [
                'description' => 'Manages images, galleries and documents, including replacing and deleting media.',
                'icon' => 'fa-solid fa-images',
                'permissions' => [
                    'admin.access', 'dashboard.view', 'galleries.view',
                    'galleries.create', 'galleries.update', 'documents.view',
                    'documents.create', 'documents.update', 'media.view',
                    'media.upload', 'media.update', 'media.replace',
                    'media.delete',
                ],
            ],
            'Auditor' => [
                'description' => 'Inspects content, users and activity logs with read-only access.',
                'icon' => 'fa-solid fa-clipboard-check',
                'permissions' => [
                    'admin.access', 'dashboard.view', 'users.view',
                    'pages.view', 'pages.revisions.view', 'news.view',
                    'galleries.view', 'documents.view', 'media.view',
                    'audit.view',
                ],
            ],
        ];
    }

    public static function description(string $name): string
    {
        return self::all()[$name]['description'] ?? 'Custom role. Review its current access before assigning it.';
    }

    public static function icon(string $name): string
    {
        return self::all()[$name]['icon'] ?? 'fa-solid fa-user-tag';
    }

    public static function order(string $name): int
    {
        $index = array_search($name, array_keys(self::all()), true);

        return $index === false ? 100 : $index;
    }
}
