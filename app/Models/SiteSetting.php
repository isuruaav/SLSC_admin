<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $site_name
 * @property string|null $site_tagline
 * @property string|null $site_name_si
 * @property string|null $site_tagline_si
 * @property string|null $address_si
 * @property string|null $commander_name_si
 * @property string|null $commander_title_si
 * @property string|null $commander_message_si
 * @property string|null $footer_text_si
 * @property string|null $maintenance_message_si
 * @property string $theme_family
 * @property int|null $logo_media_id
 * @property int|null $favicon_media_id
 * @property string|null $address
 * @property string|null $phone_primary
 * @property string|null $phone_secondary
 * @property string|null $email
 * @property string|null $map_url
 * @property string|null $commander_name
 * @property string|null $commander_title
 * @property string|null $commander_message
 * @property int|null $commander_image_media_id
 * @property string $primary_color
 * @property string $accent_color
 * @property string|null $footer_text
 * @property string|null $default_seo_title
 * @property string|null $default_seo_description
 * @property bool $maintenance_mode
 * @property string|null $maintenance_message
 * @property int|null $updated_by
 */
final class SiteSetting extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'site_name',
        'enabled_locales',
        'site_name_si',
        'site_tagline',
        'site_tagline_si',
        'theme_family',
        'logo_media_id',
        'favicon_media_id',
        'address',
        'address_si',
        'phone_primary',
        'phone_secondary',
        'email',
        'map_url',
        'commander_name',
        'commander_name_si',
        'commander_title',
        'commander_title_si',
        'commander_message',
        'commander_message_si',
        'commander_image_media_id',
        'primary_color',
        'accent_color',
        'footer_text',
        'footer_text_si',
        'default_seo_title',
        'default_seo_description',
        'maintenance_mode',
        'maintenance_message',
        'maintenance_message_si',
        'publications_title_en',
        'publications_title_si',
        'publications_kicker_en',
        'publications_kicker_si',
        'publications_subtitle_en',
        'publications_subtitle_si',
        'updated_by',
    ];

    /** @param 'site_name'|'site_tagline'|'address'|'commander_name'|'commander_title'|'commander_message'|'footer_text'|'maintenance_message' $field */
    public function localized(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        if ($locale === 'si') {
            $translated = $this->getAttribute($field.'_si');
            if (is_string($translated) && trim($translated) !== '') {
                return $translated;
            }
        }

        $english = $this->getAttribute($field);

        return is_string($english) && trim($english) !== '' ? $english : null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'enabled_locales' => 'array',
            'logo_media_id' => 'integer',
            'favicon_media_id' => 'integer',
            'commander_image_media_id' => 'integer',
            'maintenance_mode' => 'boolean',
        ];
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'logo_media_id');
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function favicon(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'favicon_media_id');
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function commanderImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'commander_image_media_id');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): self
    {
        $setting = self::query()->first();

        if ($setting instanceof self) {
            return $setting;
        }

        return self::query()->create([
            'site_name' => (string) config('app.name', 'Official Website'),
            'theme_family' => 'army-unit',
            'primary_color' => '#166534',
            'accent_color' => '#ca8a04',
        ]);
    }

    public function publicationTitle(string $locale = 'en'): string
    {
        return $locale === 'si'
            ? (trim((string) $this->publications_title_si) !== '' ? (string) $this->publications_title_si : 'සිග්නලර් සඟරාව')
            : (trim((string) $this->publications_title_en) !== '' ? (string) $this->publications_title_en : 'Signaller Magazine');
    }

    public function publicationKicker(string $locale = 'en'): string
    {
        return $locale === 'si'
            ? (trim((string) $this->publications_kicker_si) !== '' ? (string) $this->publications_kicker_si : 'අපගේ සඟරා')
            : (trim((string) $this->publications_kicker_en) !== '' ? (string) $this->publications_kicker_en : 'Explore our library');
    }

    public function publicationSubtitle(string $locale = 'en'): string
    {
        return $locale === 'si'
            ? (trim((string) $this->publications_subtitle_si) !== '' ? (string) $this->publications_subtitle_si : 'ශ්‍රී ලංකා සංඥා බලකායේ නවතම සඟරා. PDF ගොනුව කියවීමට කවරය මත ක්ලික් කරන්න.')
            : (trim((string) $this->publications_subtitle_en) !== '' ? (string) $this->publications_subtitle_en : 'Explore our latest journals and publications. Select a cover to read the PDF.');
    }
}
