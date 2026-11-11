<?php

namespace App\Services;

use App\Models\Redirect;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class RedirectService
{
    public function save(?Redirect $redirect, User $actor, string $sourcePath, string $destinationUrl, int $status, bool $isActive): Redirect
    {
        Gate::forUser($actor)->authorize('redirects.manage');

        $sourcePath = '/'.ltrim(trim($sourcePath), '/');
        if ($sourcePath === '/' || str_starts_with($sourcePath, '/admin')) {
            throw ValidationException::withMessages(['sourcePath' => 'The source path cannot be / or an admin path.']);
        }

        if (mb_strlen($sourcePath) > 500) {
            throw ValidationException::withMessages(['sourcePath' => 'The source path may not exceed 500 characters.']);
        }

        $destinationUrl = trim($destinationUrl);
        $isInternalPath = str_starts_with($destinationUrl, '/') && ! str_starts_with($destinationUrl, '//');
        $isAbsoluteHttpUrl = false;

        if (filter_var($destinationUrl, FILTER_VALIDATE_URL) !== false) {
            $scheme = parse_url($destinationUrl, PHP_URL_SCHEME);
            $isAbsoluteHttpUrl = is_string($scheme)
                && in_array(mb_strtolower($scheme), ['http', 'https'], true);
        }

        if ($destinationUrl === '' || (! $isInternalPath && ! $isAbsoluteHttpUrl)) {
            throw ValidationException::withMessages(['destinationUrl' => 'Enter a valid HTTP/HTTPS URL or internal path beginning with a single /.']);
        }

        if (mb_strlen($destinationUrl) > 2048) {
            throw ValidationException::withMessages(['destinationUrl' => 'The destination URL may not exceed 2048 characters.']);
        }

        if ($isInternalPath && $destinationUrl === $sourcePath) {
            throw ValidationException::withMessages(['destinationUrl' => 'The destination cannot be the same as the source path.']);
        }

        if (! in_array($status, [301, 302, 307, 308], true)) {
            $status = 301;
        }

        $duplicate = Redirect::query()->where('source_path', $sourcePath);
        if ($redirect instanceof Redirect) {
            $duplicate->whereKeyNot($redirect->id);
        }
        if ($duplicate->exists()) {
            throw ValidationException::withMessages(['sourcePath' => 'A redirect already exists for this path.']);
        }

        if ($isActive) {
            $links = Redirect::query()->where('is_active', true)->when($redirect !== null, fn ($query) => $query->whereKeyNot($redirect->id))->pluck('destination_url', 'source_path')->all();
            $links[$sourcePath] = $destinationUrl;
            $seen = [];
            $path = $sourcePath;
            while (isset($links[$path])) {
                if (isset($seen[$path])) {
                    throw ValidationException::withMessages(['destinationUrl' => 'This redirect would create a loop. Choose a destination outside the redirect chain.']);
                }
                $seen[$path] = true;
                $target = $links[$path];
                $host = parse_url($target, PHP_URL_HOST);
                if ($host && strcasecmp($host, (string) parse_url(config('app.url'), PHP_URL_HOST)) !== 0) {
                    break;
                }
                $path = (string) (parse_url($target, PHP_URL_PATH) ?: '/');
                $base = rtrim((string) parse_url(config('app.url'), PHP_URL_PATH), '/');
                if ($base !== '' && str_starts_with($path, $base.'/')) {
                    $path = substr($path, strlen($base));
                }
            }
        }

        return DB::transaction(function () use ($redirect, $actor, $sourcePath, $destinationUrl, $status, $isActive): Redirect {
            $model = $redirect instanceof Redirect
                ? Redirect::query()->lockForUpdate()->findOrFail($redirect->id)
                : new Redirect;

            $old = $model->exists ? $model->only(['source_path', 'destination_url', 'http_status', 'is_active']) : [];
            $model->forceFill([
                'source_path' => $sourcePath,
                'destination_url' => $destinationUrl,
                'http_status' => $status,
                'is_active' => $isActive,
                'created_by' => $model->exists ? $model->created_by : $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            app(AuditLogger::class)->log(
                event: $redirect instanceof Redirect ? 'redirects.updated' : 'redirects.created',
                description: $redirect instanceof Redirect ? 'A redirect was updated.' : 'A redirect was created.',
                actor: $actor,
                subject: $model,
                oldValues: $old,
                newValues: $model->only(['source_path', 'destination_url', 'http_status', 'is_active']),
            );

            return $model->refresh();
        }, 3);
    }

    public function delete(Redirect $redirect, User $actor): void
    {
        Gate::forUser($actor)->authorize('redirects.manage');
        app(AuditLogger::class)->log(
            event: 'redirects.deleted',
            description: 'A redirect was deleted.',
            actor: $actor,
            subject: $redirect,
            oldValues: $redirect->only(['source_path', 'destination_url', 'http_status']),
        );
        $redirect->delete();
    }
}
