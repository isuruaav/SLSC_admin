<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_AttrDef_Enum;
use HTMLPurifier_AttrDef_Text;
use HTMLPurifier_Config;
use RuntimeException;

final class PageHtmlSanitizer
{
    private HTMLPurifier $htmlPurifier;

    private HTMLPurifier $visualPurifier;

    /**
     * @var list<string>
     */
    private array $allowedElements = [
        'div',
        'section',
        'main',
        'article',
        'header',
        'footer',
        'nav',
        'aside',
        'figure',
        'figcaption',
        'details',
        'summary',
        'span',
        'p',
        'br',
        'hr',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'strong',
        'b',
        'em',
        'i',
        'u',
        's',
        'del',
        'small',
        'sub',
        'sup',
        'ul',
        'ol',
        'li',
        'blockquote',
        'pre',
        'code',
        'a',
        'img',
        'table',
        'thead',
        'tbody',
        'tfoot',
        'tr',
        'th',
        'td',
    ];

    public function __construct()
    {
        $cachePath = storage_path(
            'framework/cache/htmlpurifier-page',
        );

        if (
            ! is_dir($cachePath)
            && ! mkdir($cachePath, 0755, true)
            && ! is_dir($cachePath)
        ) {
            throw new RuntimeException(
                'Unable to create the page HTML Purifier cache directory.',
            );
        }

        $this->htmlPurifier = $this->makePurifier(
            cachePath: $cachePath,
            definitionId: 'awcms-page-tailwind-html',
            definitionRev: 8,
            allowVisualStyles: false,
        );

        $this->visualPurifier = $this->makePurifier(
            cachePath: $cachePath,
            definitionId: 'awcms-page-visual-html',
            definitionRev: 5,
            allowVisualStyles: true,
        );
    }

    /**
     * Sanitize HTML content while preserving CSS classes.
     *
     * Inline styles and style elements remain forbidden in HTML mode.
     */
    public function sanitize(?string $html): string
    {
        return $this->purify(
            $this->htmlPurifier,
            $html,
        );
    }

    /**
     * Sanitize visual editor content.
     *
     * Only text colour, background colour and text alignment
     * are allowed as inline styles.
     */
    public function sanitizeVisual(?string $html): string
    {
        return $this->purify(
            $this->visualPurifier,
            $html,
        );
    }

    private function makePurifier(
        string $cachePath,
        string $definitionId,
        int $definitionRev,
        bool $allowVisualStyles,
    ): HTMLPurifier {
        $config = HTMLPurifier_Config::createDefault();

        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Core.RemoveProcessingInstructions', true);

        $config->set(
            'HTML.AllowedElements',
            $this->allowedElements,
        );

        $allowedAttributes = [
            '*.class',
            '*.id',
            '*.title',
            'i.aria-hidden',
            'a.href',
            'a.title',
            'img.src',
            'img.alt',
            'img.width',
            'img.height',
            'th.colspan',
            'th.rowspan',
            'td.colspan',
            'td.rowspan',
        ];

        if ($allowVisualStyles) {
            $allowedAttributes[] = '*.style';
        }

        $config->set(
            'HTML.AllowedAttributes',
            $allowedAttributes,
        );

        $config->set(
            'HTML.ForbiddenElements',
            [
                'script',
                'style',
                'iframe',
                'frame',
                'frameset',
                'object',
                'embed',
                'applet',
                'form',
                'input',
                'button',
                'textarea',
                'select',
                'option',
                'link',
                'meta',
                'base',
                'svg',
                'math',
                'video',
                'audio',
                'source',
                'canvas',
            ],
        );

        $forbiddenAttributes = [
            'onclick',
            'onerror',
            'onload',
            'onmouseover',
            'onfocus',
            'onblur',
            'onchange',
            'onsubmit',
            'onkeydown',
            'onkeyup',
            'onkeypress',
            'ontoggle',
            'onbeforetoggle',
        ];

        if (! $allowVisualStyles) {
            $forbiddenAttributes[] = 'style';
        }

        $config->set(
            'HTML.ForbiddenAttributes',
            $forbiddenAttributes,
        );

        $config->set(
            'URI.AllowedSchemes',
            [
                'http' => true,
                'https' => true,
                'mailto' => true,
                'tel' => true,
            ],
        );

        $config->set('Attr.EnableID', true);

        $config->set(
            'CSS.AllowedProperties',
            $allowVisualStyles
                ? [
                    'color',
                    'background-color',
                    'text-align',
                ]
                : [],
        );

        $config->set('AutoFormat.RemoveEmpty', false);
        $config->set('Cache.SerializerPath', $cachePath);
        $config->set('HTML.DefinitionID', $definitionId);
        $config->set('HTML.DefinitionRev', $definitionRev);

        $definition = $config->maybeGetRawHTMLDefinition();

        if ($definition !== null) {
            $definition->addAttribute('i', 'aria-hidden', new HTMLPurifier_AttrDef_Enum(['true', 'false']));
            $html5BlockElements = [
                'section',
                'main',
                'article',
                'header',
                'footer',
                'nav',
                'aside',
                'figure',
                'figcaption',
            ];

            foreach ($html5BlockElements as $elementName) {
                if (isset($definition->info[$elementName])) {
                    continue;
                }

                $definition->addElement(
                    $elementName,
                    'Block',
                    'Flow',
                    'Common',
                );
            }

            /*
             * Native disclosure controls for mobile navigation.
             * No JavaScript or event attributes are permitted.
             */
            if (! isset($definition->info['details'])) {
                $definition->addElement(
                    'details',
                    'Block',
                    'Flow',
                    'Common',
                );
            }

            if (! isset($definition->info['summary'])) {
                $definition->addElement(
                    'summary',
                    'Block',
                    'Inline',
                    'Common',
                );
            }

            /*
             * Preserve Tailwind and theme class names.
             * HTML, URLs and event attributes remain sanitised.
             */
            foreach ($this->allowedElements as $elementName) {
                if (! isset($definition->info[$elementName])) {
                    continue;
                }

                $definition->info[$elementName]->attr['class'] =
                    new HTMLPurifier_AttrDef_Text;
            }
        }

        return new HTMLPurifier($config);
    }

    private function purify(
        HTMLPurifier $purifier,
        ?string $html,
    ): string {
        if (! is_string($html)) {
            return '';
        }

        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $cleanHtml = trim(
            $purifier->purify($html),
        );

        if ($cleanHtml === '') {
            return '';
        }

        $textContent = html_entity_decode(
            strip_tags($cleanHtml),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        $textContent = str_replace(
            "\u{00A0}",
            '',
            $textContent,
        );

        $hasMeaningfulMedia = preg_match(
            '/<(?:img|hr)\b/iu',
            $cleanHtml,
        ) === 1;

        if (trim($textContent) === '' && ! $hasMeaningfulMedia) {
            return '';
        }

        return $cleanHtml;
    }
}
