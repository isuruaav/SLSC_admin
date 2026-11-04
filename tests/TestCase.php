<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Features;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");
        $url = $app['config']->get("database.connections.{$connection}.url");

        // Fail before RefreshDatabase can run migrations against another database.
        if (! $app->environment('testing') || ! empty($url) || ! (
            ($connection === 'sqlite' && $database === ':memory:')
            || ($connection === 'mysql' && is_string($database) && str_starts_with($database, 'awcms_test_'))
        )) {
            throw new RuntimeException('Tests require in-memory SQLite or a dedicated awcms_test_ MySQL database.');
        }

        $app['config']->set([
            'cache.default' => 'array',
            'session.driver' => 'array',
            'queue.default' => 'sync',
            'mail.default' => 'array',
        ]);

        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('s3');
        Http::preventStrayRequests();

        return $app;
    }

    protected function skipUnlessFortifyHas(
        string $feature,
        ?string $message = null
    ): void {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped(
                $message ?? "Fortify feature [{$feature}] is not enabled."
            );
        }
    }
}
