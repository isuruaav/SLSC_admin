<?php

use App\Models\MediaAsset;
use App\Models\News;
use App\Models\NewsImage;

test('public news renders attached images after the body in a four column desktop grid', function (): void {
    $news = News::factory()
        ->published()
        ->create([
            'title' => 'Multiple Image News Test',
            'slug' => 'multiple-image-news-test',
            'content' => '<p>NEWS BODY MARKER</p>',
            'featured_image_id' => null,
        ]);

    foreach (range(1, 5) as $index) {
        $media = MediaAsset::factory()->create([
            'title' => 'News Gallery Image '.$index,
            'alt_text' => 'News Gallery Image '.$index,
        ]);

        NewsImage::query()->create([
            'news_id' => (int) $news->getKey(),
            'media_asset_id' => (int) $media->getKey(),
            'sort_order' => $index,
        ]);
    }

    $response = $this->get(
        route(
            'news.show',
            [
                'slug' => $news->slug,
            ],
        ),
    );

    $response
        ->assertOk()
        ->assertSee('sos-article-gallery', false)
        ->assertSeeInOrder([
            'NEWS BODY MARKER',
            'News Gallery Image 1',
            'News Gallery Image 2',
            'News Gallery Image 3',
            'News Gallery Image 4',
            'News Gallery Image 5',
        ]);

    $themeCss = file_get_contents(base_path('themes/slsc/assets/css/news.css'));

    expect($themeCss)->toContain(
        '.sos-article-gallery {',
        'grid-template-columns: repeat(4, minmax(0, 1fr));',
    );
});
