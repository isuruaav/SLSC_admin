<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Carbon\CarbonInterface;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property CarbonInterface|null $published_at
 */
final class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'title_si',
        'slug',
        'document_category_id',
        'description',
        'description_si',
        'document_date',
        'current_version',
        'status',
        'published_at',
        'archived_at',
        'seo_title',
        'seo_description',
        'created_by',
        'updated_by',
        'published_by',
        'archived_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'current_version' => 'integer',
            'current_version_si' => 'integer',
            'status' => DocumentStatus::class,
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::creating(static function (Document $document): void {
            $uuid = $document->getAttribute('uuid');

            if (! is_string($uuid) || trim($uuid) === '') {
                $document->setAttribute(
                    'uuid',
                    Str::uuid()->toString(),
                );
            }
        });
    }

    /**
     * @return BelongsTo<DocumentCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(
            DocumentCategory::class,
            'document_category_id',
        );
    }

    /**
     * @return HasMany<DocumentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)
            ->orderByDesc('version')
            ->orderByDesc('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function titleForLocale(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'si') {
            $sinhalaTitle = $this->getAttribute('title_si');

            if (is_string($sinhalaTitle) && trim($sinhalaTitle) !== '') {
                return trim($sinhalaTitle);
            }
        }

        $title = $this->getAttribute('title');

        return is_string($title) ? trim($title) : '';
    }

    public function descriptionForLocale(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'si') {
            $sinhalaDescription = $this->getAttribute('description_si');

            if (
                is_string($sinhalaDescription)
                && trim($sinhalaDescription) !== ''
            ) {
                return trim($sinhalaDescription);
            }
        }

        $description = $this->getAttribute('description');

        if (! is_string($description) || trim($description) === '') {
            return null;
        }

        return trim($description);
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', DocumentStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query
            ->orderByDesc('document_date')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function isPublished(): bool
    {
        $status = $this->getAttribute('status');

        if (! $status instanceof DocumentStatus || ! $status->isPublic()) {
            return false;
        }

        $publishedAt = $this->getAttribute('published_at');

        if (! $publishedAt instanceof CarbonInterface) {
            return false;
        }

        return $publishedAt->lessThanOrEqualTo(now());
    }

    public function isEditable(): bool
    {
        $status = $this->getAttribute('status');

        return $status instanceof DocumentStatus
            && $status->isEditable();
    }
}
