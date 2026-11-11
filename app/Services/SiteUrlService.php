<?php

namespace App\Services;

final class SiteUrlService
{
    /** Normalize a visitor-facing website link, rejecting unsafe or ambiguous input. */
    public function normalizeLink(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $value)) {
            return null;
        }
        if (str_starts_with($value, '#') || str_starts_with($value, '?')) {
            return $value;
        }
        if (preg_match('~^(?:https?:)?//~i', $value)) {
            $parts = parse_url($value);
            if ($parts === false || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
                return null;
            }

            return $this->portable($value);
        }
        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $value) || str_starts_with($value, '../')) {
            return null;
        }
        if (str_starts_with($value, './')) {
            $value = substr($value, 2);
        }

        return $this->portable('/'.ltrim($value, '/'));
    }

    /** Remove this installation's origin and base folder from a public URL. */
    public function portable(string $value): string
    {
        $value = trim($value);
        if ($value === '' || str_starts_with($value, '#') || str_starts_with($value, '?')) {
            return $value;
        }

        $parts = parse_url($value);
        if ($parts === false) {
            return $value;
        }
        $absolute = isset($parts['host']) || isset($parts['scheme']);
        $path = $parts['path'] ?? '/';

        $roots = array_unique([url('/'), (string) config('app.url')]);
        usort($roots, static fn (string $a, string $b): int => strlen((string) parse_url($b, PHP_URL_PATH)) <=> strlen((string) parse_url($a, PHP_URL_PATH)));
        foreach ($roots as $root) {
            $base = parse_url($root);
            if ($base === false) {
                continue;
            }
            $prefix = rtrim($base['path'] ?? '', '/');
            if ($absolute && $this->origin($parts) !== $this->origin($base)) {
                continue;
            }
            if ($absolute && (isset($parts['user']) || isset($parts['pass']))) {
                continue;
            }
            if ($prefix !== '' && $path !== $prefix && ! str_starts_with($path, $prefix.'/')) {
                continue;
            }
            if (! $absolute && ! str_starts_with($path, '/')) {
                return $value;
            }

            $path = $prefix === '' ? $path : (substr($path, strlen($prefix)) ?: '/');

            return $path.(isset($parts['query']) ? '?'.$parts['query'] : '').(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
        }

        return $value;
    }

    public function resolve(string $value): string
    {
        if (! str_starts_with($value, '/') || str_starts_with($value, '//')) {
            return $value;
        }
        $base = rtrim((string) parse_url(url('/'), PHP_URL_PATH), '/');
        if ($base === '' || $value === $base || str_starts_with($value, $base.'/') || str_starts_with($value, $base.'?') || str_starts_with($value, $base.'#')) {
            return $value;
        }

        return $base.$value;
    }

    /** Resolve application-relative references after the content has been sanitized. */
    public function resolveHtml(string $html): string
    {
        return preg_replace_callback('/<(?:a|img|video|audio|source|iframe)\b(?:[^"\'<>]|"[^"]*"|\'[^\']*\')*>/i', function (array $tag): string {
            return preg_replace_callback('/(?<![\w:-])([a-z_:][\w:.-]*)(\s*=\s*)(["\'])(.*?)\3/is', function (array $attribute): string {
                if (! in_array(strtolower($attribute[1]), ['href', 'src', 'poster'], true)) {
                    return $attribute[0];
                }
                $raw = html_entity_decode($attribute[4], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $resolved = $this->resolve($raw);
                if ($resolved === $raw) {
                    return $attribute[0];
                }

                return $attribute[1].$attribute[2].$attribute[3].htmlspecialchars($resolved, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').$attribute[3];
            }, $tag[0]) ?? $tag[0];
        }, $html) ?? $html;
    }

    /** @param array{scheme?: string, host?: string, port?: int, user?: string, pass?: string, path?: string, query?: string, fragment?: string} $parts */
    private function origin(array $parts): ?string
    {
        if (! isset($parts['host'])) {
            return null;
        }
        $scheme = strtolower($parts['scheme'] ?? (string) parse_url(url('/'), PHP_URL_SCHEME));
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        return $scheme.'://'.strtolower($parts['host']).':'.$port;
    }
}
