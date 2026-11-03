<?php

namespace App\Services;

use App\Models\SchoolLeader;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class LeadershipProfileContent
{
    public function __construct(
        private readonly PageHtmlSanitizer $htmlSanitizer,
        private readonly ThemeLayoutSanitizer $layoutSanitizer,
    ) {}

    /** @return array{html_en: string, html_si: string, css: string, compiled_css: string} */
    public function prepare(string $htmlEn, string $htmlSi, string $css): array
    {
        $cleanEn = $this->htmlSanitizer->sanitize($htmlEn);
        $cleanSi = $this->htmlSanitizer->sanitize($htmlSi);

        try {
            $cleanCss = $this->layoutSanitizer->sanitizeCss($css);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'profileCss' => $exception->validator->errors()->first(),
            ]);
        }

        $candidates = [];
        preg_match_all('/\bclass\s*=\s*(["\'])(.*?)\1/si', $cleanEn.' '.$cleanSi, $matches);

        foreach ($matches[2] as $classes) {
            $decoded = html_entity_decode($classes, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            foreach (preg_split('/\s+/', trim($decoded)) ?: [] as $candidate) {
                if ($candidate === '' || strlen($candidate) > 500
                    || preg_match('/[<>\\\\]|(?:url|image-set|cross-fade|expression)\s*\(|(?:javascript|data|vbscript):/i', $candidate)) {
                    continue;
                }
                $candidates[$candidate] = $candidate;
            }
        }

        if (count($candidates) > 4000) {
            throw ValidationException::withMessages(['profileHtmlEn' => 'Use at most 4,000 distinct Tailwind classes across both languages.']);
        }

        $compiledCss = '';
        if ($cleanEn !== '' || $cleanSi !== '') {
            $process = new Process(['node', base_path('scripts/compile-leadership-profile.mjs')], base_path());
            $process->setInput(json_encode(['candidates' => array_values($candidates)], JSON_THROW_ON_ERROR));
            $process->setTimeout(30);

            try {
                $process->mustRun();
            } catch (ProcessFailedException $exception) {
                report($exception);
                throw ValidationException::withMessages([
                    'profileHtmlEn' => 'Tailwind CSS could not be generated. Check that Node.js and project dependencies are installed on the server.',
                ]);
            }
            $compiledCss = $process->getOutput();
        }

        return ['html_en' => $cleanEn, 'html_si' => $cleanSi, 'css' => $cleanCss, 'compiled_css' => $compiledCss];
    }

    public function forLeader(SchoolLeader $leader, string $locale): string
    {
        return $this->document(
            $locale === 'si' ? ($leader->profile_html_si ?? '') : ($leader->profile_html_en ?? ''),
            $leader->profile_css ?? '',
            $leader->profile_compiled_css ?? '',
            $locale,
        );
    }

    public function document(string $html, string $css, string $compiledCss, string $locale): string
    {
        $safeHtml = $this->htmlSanitizer->sanitize($html);
        $safeCss = $this->layoutSanitizer->sanitizeCss($css);
        $activeTheme = config('awcms.active_theme');
        $themeCssPath = null;
        if ($activeTheme) {
            if (is_file(base_path("themes/{$activeTheme}/assets/css/base.css"))) {
                $themeCssPath = base_path("themes/{$activeTheme}/assets/css/base.css");
            } elseif (is_file(base_path("themes/{$activeTheme}/assets/css/style.css"))) {
                $themeCssPath = base_path("themes/{$activeTheme}/assets/css/style.css");
            } elseif (is_file(base_path("themes/{$activeTheme}/assets/css/theme.css"))) {
                $themeCssPath = base_path("themes/{$activeTheme}/assets/css/theme.css");
            }
        }
        if (! $themeCssPath && is_file(base_path('themes/slsc/assets/css/base.css'))) {
            $themeCssPath = base_path('themes/slsc/assets/css/base.css');
        }
        $themeCss = is_file($themeCssPath ?? '') ? (file_get_contents($themeCssPath) ?: '') : '';
        preg_match('/:root\s*\{[^}]*\}/s', $themeCss, $root);
        $palette = $root[0] ?? '';
        $styles = str_replace('<', '\\3c ', $compiledCss."\n".$palette."\n".$safeCss);
        $baseUrl = htmlspecialchars(rtrim(url('/'), '/').'/', ENT_QUOTES, 'UTF-8');
        $language = $locale === 'si' ? 'si' : 'en';

        return '<!DOCTYPE html><html lang="'.$language.'"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width,initial-scale=1"><base href="'.$baseUrl.'">'
            .'<style>'.$styles.'</style><style>html{background:transparent}body{display:flow-root;margin:0;overflow-wrap:anywhere;font-family:system-ui,sans-serif;line-height:1.7;color:var(--text,#111936)}img{max-width:100%;height:auto}*,*::before,*::after{box-sizing:border-box}</style>'
            .'</head><body>'.$safeHtml.'</body></html>';
    }
}
