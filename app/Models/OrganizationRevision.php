<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property array{attributes: array<string, mixed>, translations: list<array<string, mixed>>, blocks: list<array<string, mixed>>} $snapshot
 */
final class OrganizationRevision extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }
}
