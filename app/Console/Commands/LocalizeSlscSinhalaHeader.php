<?php

namespace App\Console\Commands;

use App\Models\ThemeLayout;
use App\Models\ThemeLayoutRevision;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class LocalizeSlscSinhalaHeader extends Command
{
    protected $signature = 'awcms:theme:localize-sinhala-header {--apply : Save translated labels with backups and revision history}';

    protected $description = 'Translate known English labels in the existing SLSC Sinhala header, preserving its design and links.';

    public function handle(AuditLogger $audit): int
    {
        $labels = json_decode(File::get(base_path('themes/slsc/lang/si/navigation.json')), true, 512, JSON_THROW_ON_ERROR);
        $changed = 0;

        DB::transaction(function () use ($labels, $audit, &$changed): void {
            foreach (ThemeLayout::query()->where('theme_slug', 'slsc')->where('locale', 'si')->where('region', 'header')->lockForUpdate()->get() as $layout) {
                $published = $this->translate($layout->published_html ?? '', $labels);
                $draft = $this->translate($layout->draft_html ?? '', $labels);
                if ($published === ($layout->published_html ?? '') && $draft === ($layout->draft_html ?? '')) {
                    continue;
                }
                $changed++;
                $this->info('Sinhala header '.$layout->id.': '.($this->option('apply') ? 'saving translated labels' : 'translation available (dry run)'));
                if (! $this->option('apply')) {
                    continue;
                }

                $backupDirectory = storage_path('app/private/bilingual-backups');
                File::ensureDirectoryExists($backupDirectory);
                File::put($backupDirectory.'/header-'.$layout->id.'-'.now()->format('Ymd-His-u').'.json', json_encode($layout->getAttributes(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

                $revision = max($layout->revision_number, (int) $layout->revisions()->max('revision_number')) + 1;
                foreach ([$layout->published_html, $published] as $html) {
                    ThemeLayoutRevision::query()->create([
                        'theme_layout_id' => $layout->id, 'revision_number' => $revision++,
                        'content_html' => $html, 'content_css' => $layout->published_css,
                        'status' => $layout->status->value, 'created_by' => null,
                    ]);
                }
                $layout->forceFill(['published_html' => $published, 'draft_html' => $draft, 'revision_number' => $revision - 1])->save();
                $audit->log(event: 'theme-layouts.sinhala-labels-localized', description: 'Known English navigation labels were translated into Sinhala.', subject: $layout);
            }
        });

        $this->info($changed.' header(s) '.($this->option('apply') ? 'updated.' : 'would change. Use --apply to save.'));

        return self::SUCCESS;
    }

    /** @param array<string, string> $labels */
    private function translate(string $html, array $labels): string
    {
        return preg_replace_callback('/>([^<>]+)</u', static function (array $match) use ($labels): string {
            $label = trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (! isset($labels[$label])) {
                return $match[0];
            }

            return '>'.preg_replace('/\S(?:.*\S)?/su', htmlspecialchars($labels[$label], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $match[1]).'<';
        }, $html) ?? $html;
    }
}
