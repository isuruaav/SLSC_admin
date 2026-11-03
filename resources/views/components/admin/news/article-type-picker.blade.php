@props(['value' => '', 'categories' => []])

<fieldset {{ $attributes->class(['news-type-picker']) }} aria-describedby="news-type-help{{ $errors->has('categoryId') ? ' news-type-error' : '' }}">
    <legend class="news-type-picker__legend">Article type</legend>
    <p id="news-type-help" class="news-type-picker__help">Choose the article category. Save and publish your changes to update the website.</p>

    <div class="news-type-picker__options">
        @forelse ($categories as $category)
        <label class="news-type-option" wire:key="article-category-{{ $category->id }}">
            <input type="radio" name="categoryId" wire:model.live="categoryId" value="{{ $category->id }}"
                @checked((string) $value === (string) $category->id) aria-describedby="article-category-description-{{ $category->id }}">
            <span class="news-type-option__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
                    @if ($category->newsType() === 'photo_story')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <circle cx="12" cy="13" r="3" />
                    @else
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2M7 16h6M7 8h6v4H7V8z" />
                    @endif
                </svg>
            </span>
            <span class="news-type-option__content">
                <span class="news-type-option__title">{{ $category->name }}</span>
                @if ($category->newsType() === 'photo_story')
                <span class="news-type-option__translation" lang="si">ඡායාරූප පුවත්</span>
                <span id="article-category-description-{{ $category->id }}" class="news-type-option__description">Appears in Photo Stories, with a prominent photo and supporting text.</span>
                @else
                    @if ($category->slug === 'news-feature')
                        <span class="news-type-option__translation" lang="si">පුවත් විශේෂාංග</span>
                    @endif
                    <span id="article-category-description-{{ $category->id }}" class="news-type-option__description">Appears in News Features, with a thumbnail and article summary.</span>
                @endif
            </span>
        </label>
        @empty
            <p class="news-type-picker__error">Create an active news category before adding an article.</p>
        @endforelse
    </div>

    @error('categoryId')
        <p id="news-type-error" class="news-type-picker__error" role="alert">{{ $message }}</p>
    @enderror
</fieldset>
