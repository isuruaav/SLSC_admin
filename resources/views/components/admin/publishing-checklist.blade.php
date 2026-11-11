@props(['title' => '', 'content' => '', 'translatedTitle' => '', 'blocks' => []])
@php
    $linkChecks = app(\App\Services\ContentLinkInspector::class)->inspectHtml((string) $content);
    $broken = collect($linkChecks)->where('status', 'broken')->count();
    $hasContent = trim(strip_tags((string) $content)) !== '' || !empty($blocks);
@endphp
<details class="rounded-xl border border-zinc-200 bg-zinc-50 p-4" data-preserve-table>
    <summary class="cursor-pointer text-sm font-semibold">Publishing checks</summary>
    <ul class="mt-3 space-y-2 text-sm text-zinc-700">
        <li>{{ trim((string) $title) !== '' ? '✓ Title is present' : '• Add a title before publishing' }}</li>
        <li>{{ $hasContent ? '✓ Content is present' : '• Check the article or page content' }}</li>
        <li>{{ trim((string) $translatedTitle) !== '' ? '✓ Translation title is present' : '• Review the linked language version if this content needs a translation' }}</li>
        <li>{{ $broken === 0 ? '✓ No missing internal targets found in the content links' : '• '.$broken.' internal link(s) need attention' }}</li>
    </ul>
    <p class="mt-3 text-xs text-zinc-500">These checks help with review. Confirm names, dates, image descriptions and mobile appearance before publishing.</p>
    <x-admin.link-results :results="$linkChecks" />
</details>
