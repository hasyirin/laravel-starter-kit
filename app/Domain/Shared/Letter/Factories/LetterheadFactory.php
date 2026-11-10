<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Factories;

use App\Domain\Shared\Letter\Enums\LetterheadImage;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends Factory<Letterhead>
 */
class LetterheadFactory extends Factory
{
    protected $model = Letterhead::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, true),
        ];
    }

    public function withImages(int $width = 2480, int $headerHeight = 400, int $footerHeight = 200): static
    {
        return $this->afterCreating(function (Letterhead $letterhead) use ($width, $headerHeight, $footerHeight): void {
            foreach ([LetterheadImage::Header->value => $headerHeight, LetterheadImage::Footer->value => $footerHeight] as $collection => $height) {
                $letterhead->addMediaFromString(UploadedFile::fake()->image("{$collection}.png", $width, $height)->getContent())
                    ->usingFileName("{$collection}.png")
                    ->toMediaCollection($collection);
            }
        });
    }
}
