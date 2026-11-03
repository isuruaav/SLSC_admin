<?php

namespace App\Services;

use JsonException;

/**
 * @phpstan-type ThemeManifest array{
 *     name: string,
 *     slug: string,
 *     version: string,
 *     author: string,
 *     description: string,
 *     supports: list<string>,
 *     views: array{layout: string, home: string},
 *     assets: array{css: string, js: string}
 * }
 */
final class ThemeManager
{
    /**
     * @return array<string, ThemeManifest>
     */
    public function all(): array
    {
        $directories = glob($this->themesPath('*'), GLOB_ONLYDIR);

        if ($directories === false) {
            return [];
        }

        $themes = [];

        foreach ($directories as $directory) {
            $manifest = $this->readManifest($directory);

            if ($manifest === null) {
                continue;
            }

            $themes[$manifest['slug']] = $manifest;
        }

        ksort($themes);

        return $themes;
    }

    /**
     * @return ThemeManifest|null
     */
    public function find(string $slug): ?array
    {
        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug) !== 1) {
            return null;
        }

        return $this->readManifest(
            $this->themesPath($slug),
        );
    }

    public function viewsPath(string $slug): ?string
    {
        if ($this->find($slug) === null) {
            return null;
        }

        $viewsPath = $this->themesPath(
            $slug.DIRECTORY_SEPARATOR.'views',
        );

        return is_dir($viewsPath)
            ? $viewsPath
            : null;
    }

    private function themesPath(string $path = ''): string
    {
        $root = base_path('themes');

        return $path === ''
            ? $root
            : $root.DIRECTORY_SEPARATOR.$path;
    }

    /**
     * @return ThemeManifest|null
     */
    private function readManifest(string $directory): ?array
    {
        if (! is_dir($directory)) {
            return null;
        }

        $directoryName = basename($directory);

        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $directoryName) !== 1) {
            return null;
        }

        $manifestPath = $directory.DIRECTORY_SEPARATOR.'theme.json';

        if (! is_file($manifestPath)) {
            return null;
        }

        $contents = @file_get_contents($manifestPath);

        if (! is_string($contents)) {
            return null;
        }

        try {
            $decoded = json_decode(
                $contents,
                true,
                32,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        $name = $decoded['name'] ?? null;
        $slug = $decoded['slug'] ?? null;
        $version = $decoded['version'] ?? null;
        $author = $decoded['author'] ?? null;
        $description = $decoded['description'] ?? null;
        $supports = $decoded['supports'] ?? null;
        $views = $decoded['views'] ?? null;
        $assets = $decoded['assets'] ?? null;

        if (
            ! is_string($name)
            || trim($name) === ''
            || ! is_string($slug)
            || $slug !== $directoryName
            || ! is_string($version)
            || trim($version) === ''
            || ! is_string($author)
            || trim($author) === ''
            || ! is_string($description)
            || ! is_array($supports)
            || ! is_array($views)
            || ! is_array($assets)
        ) {
            return null;
        }

        $normalisedSupports = [];

        foreach ($supports as $support) {
            if (! is_string($support) || preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $support) !== 1) {
                return null;
            }

            $normalisedSupports[] = $support;
        }

        $layoutView = $this->safeRelativePath($views['layout'] ?? null);
        $homeView = $this->safeRelativePath($views['home'] ?? null);
        $cssAsset = $this->safeRelativePath($assets['css'] ?? null);
        $jsAsset = $this->safeRelativePath($assets['js'] ?? null);

        if (
            $layoutView === null
            || $homeView === null
            || $cssAsset === null
            || $jsAsset === null
        ) {
            return null;
        }

        return [
            'name' => trim($name),
            'slug' => $slug,
            'version' => trim($version),
            'author' => trim($author),
            'description' => trim($description),
            'supports' => array_values(array_unique($normalisedSupports)),
            'views' => [
                'layout' => $layoutView,
                'home' => $homeView,
            ],
            'assets' => [
                'css' => $cssAsset,
                'js' => $jsAsset,
            ],
        ];
    }

    private function safeRelativePath(mixed $path): ?string
    {
        if (
            ! is_string($path)
            || $path === ''
            || str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || str_contains($path, '..')
            || preg_match('/\A[a-zA-Z0-9_\/. -]+\z/', $path) !== 1
        ) {
            return null;
        }

        return $path;
    }
}
