<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Models;

use App\Domain\Shared\Common\Concerns\Models\InteractsWithMedia;
use App\Domain\Shared\Common\Contracts\Models\HasMedia;
use App\Domain\Shared\Common\Models\Model;
use App\Domain\Shared\Letter\Enums\LetterheadImage;
use App\Domain\Shared\Letter\Factories\LetterheadFactory;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use LogicException;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $branch_id
 * @property int|null $department_id
 * @property string $name
 */
class Letterhead extends Model implements HasMedia
{
    /** @use HasFactory<LetterheadFactory> */
    use HasFactory;

    use InteractsWithMedia;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'department_id',
        'name',
    ];

    protected static function newFactory(): LetterheadFactory
    {
        return LetterheadFactory::new();
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function registerMediaCollections(): void
    {
        foreach (LetterheadImage::cases() as $image) {
            $this->addMediaCollection($image->value)->singleFile();
        }
    }

    /**
     * @return array{src: string, heightMm: float}|null
     */
    public function printableImage(LetterheadImage $image): ?array
    {
        $media = $this->getMedia($image)->first();

        if ($media === null) {
            return null;
        }

        $contents = (string) Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
        $size = getimagesizefromstring($contents);

        throw_if($size === false, LogicException::class, "The {$image->value} image of letterhead [{$this->name}] is not an image.");

        return [
            'src' => 'data:'.$size['mime'].';base64,'.base64_encode($contents),
            'heightMm' => $size[1] / $size[0] * 210,
        ];
    }
}
