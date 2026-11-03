<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

final class ThemeLayoutSanitizer
{
    /** @var list<string> */
    public const ALLOWED_PLACEHOLDERS = [
        'site_logo',
        'site_name',
        'site_tagline',
        'primary_menu',
        'responsive_primary_navigation',
        'mobile_menu_toggle',
        'language_switcher',
        'social_links',
        'contact_details',
        'copyright_year',
        'search_button',
        'search_bar',
        'search_advanced',
    ];

    private const MAX_HTML_LENGTH = 100_000;

    private const MAX_CSS_LENGTH = 50_000;

    public function __construct(
        private readonly PageHtmlSanitizer $pageHtmlSanitizer,
    ) {}

    public function sanitizeHtml(?string $html): string
    {
        if (! is_string($html) || trim($html) === '') {
            return '';
        }

        if (mb_strlen($html) > self::MAX_HTML_LENGTH) {
            throw ValidationException::withMessages([
                'layout_html' => 'Header or footer HTML may not exceed 100,000 characters.',
            ]);
        }

        $this->rejectExecutableTemplateSyntax($html);

        [$protectedHtml, $protectedUrlPlaceholders] =
            $this->protectSiteUrlPlaceholders($html);

        $this->validatePlaceholders($protectedHtml);

        $cleanHtml = $this->pageHtmlSanitizer->sanitize($protectedHtml);

        return str_replace(
            array_keys($protectedUrlPlaceholders),
            array_values($protectedUrlPlaceholders),
            $cleanHtml,
        );
    }

    public function sanitizeCss(?string $css): string
    {
        if (! is_string($css) || trim($css) === '') {
            return '';
        }

        $css = trim($css);

        if (mb_strlen($css) > self::MAX_CSS_LENGTH) {
            throw ValidationException::withMessages([
                'layout_css' => 'Header or footer CSS may not exceed 50,000 characters.',
            ]);
        }

        // HTML closes a style element even when the closing tag is in a CSS comment.
        if (preg_match('/<\/?style\b/i', $css) === 1) {
            throw ValidationException::withMessages([
                'layout_css' => 'Enter CSS only, without <style> tags.',
            ]);
        }

        $inspectionCss = preg_replace(
            '/\/\*.*?\*\//s',
            '',
            $css,
        );

        if (is_string($inspectionCss)) {
            // Inspect decoded tokens while retaining icon and selector escapes in the saved source.
            $inspectionCss = preg_replace('/\\\\(?:\r\n|[\n\r\f])/', '', $inspectionCss);
        }

        if (is_string($inspectionCss)) {
            $inspectionCss = preg_replace_callback(
                '/\\\\(?:([0-9a-f]{1,6})(?:\r\n|[ \t\r\n\f])?|([^\r\n\f]))/iu',
                static function (array $matches): string {
                    if ($matches[1] === '') {
                        return $matches[2];
                    }

                    $codePoint = (int) hexdec($matches[1]);

                    return $codePoint === 0 || $codePoint > 0x10FFFF
                        || ($codePoint >= 0xD800 && $codePoint <= 0xDFFF)
                            ? "\u{FFFD}"
                            : mb_chr($codePoint, 'UTF-8');
                },
                $inspectionCss,
            );
        }

        if (! is_string($inspectionCss)) {
            throw ValidationException::withMessages([
                'layout_css' => 'The custom CSS could not be validated.',
            ]);
        }

        $forbiddenPatterns = [
            '/<\/?style\b/i',
            '/@(?:import|charset|namespace|font-face)\b/i',
            '/\b(?:url|image-set|cross-fade|expression)\s*\(/i',
            '/(?<![\w-])(?:behavior|-moz-binding)\s*:/i',
            '/(?:javascript|data|vbscript)\s*:/i',
        ];

        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $inspectionCss) === 1) {
                throw ValidationException::withMessages([
                    'layout_css' => 'Custom CSS contains a blocked construct.',
                ]);
            }
        }

        return $css;
    }

    private function rejectExecutableTemplateSyntax(string $html): void
    {
        $forbiddenPatterns = [
            '/<\?(?:php|=)?/i',
            '/\{\{|\}\}|\{!!|!!\}/',
            '/@(?:php|endphp|include|includeif|extends|section|yield|stack|push|inject|once|verbatim|auth|guest|can|cannot|foreach|for|while|if|unless|switch)\b/i',
            '/<\s*(?:script|iframe|object|embed|applet|form|input|button|textarea|select|option|link|meta|base|svg|math|video|audio|canvas)\b/i',
        ];

        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $html) === 1) {
                throw ValidationException::withMessages([
                    'layout_html' => 'Executable or unsafe template syntax is not allowed.',
                ]);
            }
        }
    }

    /**
     * Temporarily replaces safe local URL placeholders before HTML purification.
     *
     * Only an exact quoted href value may contain this placeholder:
     * href="[[site_url:/local/path]]"
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function protectSiteUrlPlaceholders(string $html): array
    {
        $matchCount = preg_match_all(
            '/\[\[site_url:(\/[A-Za-z0-9._~\/%\-]*)\]\]/i',
            $html,
            $matches,
        );

        if ($matchCount === false || $matchCount === 0) {
            return [$html, []];
        }

        /** @var list<string> $matchedPlaceholders */
        $matchedPlaceholders = array_values(array_unique($matches[0]));

        /** @var array<string, string> $protectedPlaceholders */
        $protectedPlaceholders = [];

        foreach ($matchedPlaceholders as $placeholder) {
            $totalOccurrences = substr_count($html, $placeholder);

            $hrefPattern = '/\bhref\s*=\s*(["\'])'
                .preg_quote($placeholder, '/')
                .'\1/i';

            $hrefOccurrences = preg_match_all($hrefPattern, $html);

            if (
                $hrefOccurrences === false
                || $hrefOccurrences !== $totalOccurrences
            ) {
                throw ValidationException::withMessages([
                    'layout_html' => 'Site URL placeholders may only be used as the complete value of an href attribute.',
                ]);
            }

            $marker = '/__awcms_site_url_'
                .hash('sha256', strtolower($placeholder))
                .'__';

            $html = str_replace($placeholder, $marker, $html);
            $protectedPlaceholders[$marker] = $placeholder;
        }

        return [$html, $protectedPlaceholders];
    }

    private function validatePlaceholders(string $html): void
    {
        if (
            preg_match(
                '/<[^>]*\[\[[^\[\]]+\]\][^>]*>/i',
                $html,
            ) === 1
        ) {
            throw ValidationException::withMessages([
                'layout_html' => 'CMS placeholders must be placed in HTML content, not inside tags or attributes.',
            ]);
        }

        $matchCount = preg_match_all(
            '/\[\[([^\[\]]+)\]\]/',
            $html,
            $matches,
        );

        if ($matchCount === false || $matchCount === 0) {
            return;
        }

        /** @var list<non-empty-string> $placeholders */
        $placeholders = $matches[1];

        foreach ($placeholders as $placeholder) {
            $normalizedPlaceholder = strtolower(trim($placeholder));

            if (
                ! in_array(
                    $normalizedPlaceholder,
                    self::ALLOWED_PLACEHOLDERS,
                    true,
                )
            ) {
                throw ValidationException::withMessages([
                    'layout_html' => 'Unknown header or footer placeholder: [['.$placeholder.']].',
                ]);
            }
        }
    }
}
