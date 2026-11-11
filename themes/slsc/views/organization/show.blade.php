@extends('theme-slsc::layout')

@section('title', $seoTitle)
@section('description', $seoDescription)
@section('meta_description', $seoDescription)

@section('content')
<div class="org-page">
    <div class="container">
        @include('theme-slsc::partials.page-heading', [
            'heading' => $title,
            'headingIntro' => $subtitle,
            'headingEyebrow' => $organization->category?->localizedName($currentLocale),
        ])
        @if ($organization->status->value !== 'published')
            <p class="org-cat-badge">{{ $currentLocale === 'si' ? 'කෙටුම්පත් පෙරදසුන' : 'Draft Preview' }}</p>
        @endif
    </div>

    <!-- Detail Body -->
    <div class="container org-detail-body">
        <div class="org-detail-cols">
            <!-- Left Main Content Column -->
            <div class="org-detail-main">
                <!-- Summary Callout -->
                @if ($summary)
                    <div class="org-summary-box">
                        <h3>Executive Summary</h3>
                        <p>{{ $summary }}</p>
                    </div>
                @endif

                <!-- Dynamic Content Blocks -->
                @forelse ($contentBlocks as $block)
                    <div class="org-block">
                        {{-- 1. HEADING --}}
                        @if ($block->block_type === 'heading')
                            @php
                                $level = $block->structured_data['level'] ?? 'h2';
                            @endphp
                            @if ($level === 'h3')
                                <h3 class="org-heading-h3">{{ $block->content ?: $block->title }}</h3>
                            @else
                                <h2 class="org-heading-h2">{{ $block->content ?: $block->title }}</h2>
                            @endif

                        {{-- 2. PARAGRAPH & RICH TEXT --}}
                        @elseif ($block->block_type === 'paragraph' || $block->block_type === 'rich_text')
                            @if ($block->title)
                                <h3 class="org-heading-h3">{{ $block->title }}</h3>
                            @endif
                            <div class="org-paragraph">
                                {!! nl2br(e($block->content)) !!}
                            </div>

                        {{-- 3. DYNAMIC DATA TABLE --}}
                        @elseif ($block->block_type === 'table')
                            @if ($block->title)
                                <h3 class="org-heading-h3">{{ $block->title }}</h3>
                            @endif
                            <div class="org-table-container">
                                <table class="org-table">
                                    <thead>
                                        <tr>
                                            @foreach ($block->structured_data['headers'] ?? [] as $header)
                                                <th>{{ $header }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($block->structured_data['rows'] ?? [] as $row)
                                            <tr>
                                                @foreach ($row as $cell)
                                                    <td>{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                        {{-- 4. CONTACT BLOCK --}}
                        @elseif ($block->block_type === 'contact')
                            <div class="org-contact-card">
                                <h4>
                                    <i class="fa-solid fa-address-book"></i>
                                    <span>{{ $block->title ?: 'Unit Contacts' }}</span>
                                </h4>
                                @if ($block->content)
                                    <p class="org-contact-description">{{ $block->content }}</p>
                                @endif
                                <div class="org-contact-grid">
                                    @if (! empty($block->structured_data['telephone']))
                                        <div class="org-contact-item">
                                            <i class="fa-solid fa-phone"></i>
                                            <span>{{ $block->structured_data['telephone'] }}</span>
                                        </div>
                                    @endif
                                    @if (! empty($block->structured_data['email']))
                                        <div class="org-contact-item">
                                            <i class="fa-solid fa-envelope"></i>
                                            <span>{{ $block->structured_data['email'] }}</span>
                                        </div>
                                    @endif
                                    @if (! empty($block->structured_data['location']))
                                        <div class="org-contact-item">
                                            <i class="fa-solid fa-location-dot"></i>
                                            <span>{{ $block->structured_data['location'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="org-empty" style="padding: 40px;">
                        <p>Detailed historical content is being updated by the Regimental Secretariat.</p>
                    </div>
                @endforelse
            </div>

            <!-- Right Sidebar Column -->
            <aside class="org-detail-sidebar">
                <!-- Unit Quick Facts Card -->
                <div class="org-sidebar-card">
                    <h3 class="org-sidebar-heading">Unit Quick Facts</h3>
                    <div class="org-facts-list">
                        @if ($organization->short_name)
                            <div>
                                <div class="org-fact-label">Designation</div>
                                <div class="org-fact-val mono">{{ $organization->short_name }}</div>
                            </div>
                        @endif
                        @if ($organization->category)
                            <div>
                                <div class="org-fact-label">Category</div>
                                <div class="org-fact-val">{{ $organization->category->localizedName($currentLocale) }}</div>
                            </div>
                        @endif
                        @if ($organization->location)
                            <div>
                                <div class="org-fact-label">Station / Location</div>
                                <div class="org-fact-val">{{ $organization->location }}</div>
                            </div>
                        @endif
                        @if ($organization->telephone)
                            <div>
                                <div class="org-fact-label">Telephone</div>
                                <div class="org-fact-val mono">{{ $organization->telephone }}</div>
                            </div>
                        @endif
                        @if ($organization->email)
                            <div>
                                <div class="org-fact-label">Email</div>
                                <div class="org-fact-val">
                                    <a href="mailto:{{ $organization->email }}" >{{ $organization->email }}</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Related Units -->
                @if ($relatedUnits->isNotEmpty())
                    <div class="org-sidebar-card">
                        <h3 class="org-sidebar-heading">Related Formations</h3>
                        <div>
                            @foreach ($relatedUnits as $rel)
                                @php
                                    $relUrl = $currentLocale === 'en'
                                        ? route('organization.show', ['slug' => $rel->slug])
                                        : route('organization.show.localized', ['locale' => $currentLocale, 'slug' => $rel->slug]);
                                @endphp
                                <a href="{{ $relUrl }}" class="org-related-link">
                                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $rel->titleForLocale($currentLocale) }}</span>
                                    <i class="fa-solid fa-chevron-right" style="font-size: 10px; color: #64748b;"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Back to All Organizations -->
                <div>
                    <a href="{{ $currentLocale === 'en' ? route('organization.index') : route('organization.index.localized', ['locale' => $currentLocale]) }}" class="org-back-btn">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Back to All Organizations</span>
                    </a>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
