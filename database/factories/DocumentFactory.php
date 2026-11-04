<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
final class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'uuid' => Str::uuid()->toString(),
            'title' => $title,
            'title_si' => null,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 999999),
            'document_category_id' => null,
            'description' => fake()->paragraph(),
            'description_si' => null,
            'document_date' => fake()->date(),
            'current_version' => 0,
            'current_version_si' => 0,
            'status' => DocumentStatus::Draft->value,
            'published_at' => null,
            'archived_at' => null,
            'seo_title' => null,
            'seo_description' => null,
            'created_by' => null,
            'updated_by' => null,
            'published_by' => null,
            'archived_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DocumentStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);
    }
}
