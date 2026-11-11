<?php

namespace App\Models;

use App\Enums\PageEditorMode;
use App\Enums\PageLocale;
use App\Enums\PageStatus;
use App\Services\ContentSanitizer;
use App\Services\PageCss;
use App\Services\PageHtmlSanitizer;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $guarded = [
        'id',
        'active_slug',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'locale' => PageLocale::class,
            'show_title' => 'boolean',
            'editor_mode' => PageEditorMode::class,
            'status' => PageStatus::class,
            'robots_index' => 'boolean',
            'submitted_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<PageRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(
            PageRevision::class,
        )->orderByDesc(
            'revision_number',
        );
    }

    /**
     * @return HasMany<Page, $this>
     */
    public function translationVersions(): HasMany
    {
        return $this->hasMany(
            self::class,
            'translation_group',
            'translation_group',
        )->orderBy('locale');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by',
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by',
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by',
        );
    }

    public function setExcerptAttribute(mixed $value): void
    {
        $rawExcerpt = is_string($value)
            ? $value
            : null;

        $excerpt = app(
            ContentSanitizer::class,
        )->plainText(
            $rawExcerpt,
            500,
        );

        $this->attributes['excerpt'] = $excerpt !== ''
            ? $excerpt
            : null;
    }

    protected static function booted(): void
    {
        self::creating(
            function (Page $page): void {
                $attributes = $page->getAttributes();

                $rawLocale = $attributes['locale'] ?? null;

                if (! is_string($rawLocale) || PageLocale::tryFrom($rawLocale) === null) {
                    $page->setAttribute(
                        'locale',
                        PageLocale::English->value,
                    );
                }

                $translationGroup = $attributes['translation_group'] ?? null;

                if (! is_string($translationGroup) || trim($translationGroup) === '') {
                    $page->setAttribute(
                        'translation_group',
                        (string) Str::uuid(),
                    );
                }
            },
        );

        self::saving(
            function (Page $page): void {
                $attributes = $page->getAttributes();

                $rawContent = $attributes['content'] ?? null;

                $rawCss = $attributes['custom_css'] ?? null;

                if (is_string($rawCss)) {
                    $cssError = app(PageCss::class)->error($rawCss);

                    if ($cssError !== null) {
                        throw ValidationException::withMessages(['customCss' => $cssError]);
                    }

                    $attributes['custom_css'] = trim($rawCss) !== '' ? trim($rawCss) : null;
                }

                $content = is_string($rawContent)
                    ? $rawContent
                    : null;

                $rawEditorMode = $attributes['editor_mode'] ?? null;

                $editorMode = is_string($rawEditorMode)
                    ? $rawEditorMode
                    : PageEditorMode::Html->value;

                $sanitizer = app(
                    PageHtmlSanitizer::class,
                );

                $cleanContent = $editorMode === PageEditorMode::Visual->value
                    ? $sanitizer->sanitizeVisual(
                        $content,
                    )
                    : $sanitizer->sanitize(
                        $content,
                    );

                $attributes['content'] = $cleanContent !== ''
                    ? $cleanContent
                    : null;

                $page->setRawAttributes(
                    $attributes,
                );
            },
        );
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where(
                'status',
                PageStatus::Published->value,
            )
            ->whereNotNull('published_at')
            ->where(
                'published_at',
                '<=',
                now(),
            );
    }
}
