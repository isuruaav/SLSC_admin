<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $subject
 * @property string $message
 * @property string $status
 * @property CarbonImmutable|null $read_at
 * @property CarbonImmutable|null $resolved_at
 * @property int|null $handled_by
 * @property string|null $ip_address
 */
final class ContactMessage extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'read_at',
        'resolved_at',
        'handled_by',
        'ip_address',
        'reference', 'district', 'interest', 'consented_at', 'assigned_to', 'internal_notes',
    ];

    protected static function booted(): void
    {
        self::creating(static function (ContactMessage $message): void {
            $uuid = $message->getAttribute('uuid');

            if (! is_string($uuid) || trim($uuid) === '') {
                $message->setAttribute(
                    'uuid',
                    (string) Str::uuid(),
                );
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'read_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
            'consented_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
