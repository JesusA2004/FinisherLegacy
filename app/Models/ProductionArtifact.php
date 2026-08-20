<?php

namespace App\Models;

use App\Enums\PlateBackTransform;
use Database\Factories\ProductionArtifactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A frozen pair of production files (front + back) for exactly one
 * ProductionJob — never regenerated or overwritten after creation. See
 * App\Services\Production\ProductionArtifactService (the only writer) and
 * docs/adr/0003-production-state-machine.md §Artifact.
 */
#[Fillable([
    'production_job_id', 'plate_id', 'plate_template_version_id', 'renderer_version', 'format',
    'front_storage_path', 'front_sha256', 'back_storage_path', 'back_sha256',
    'width_mm', 'height_mm', 'back_transform', 'metadata', 'generated_at',
])]
class ProductionArtifact extends Model
{
    /** @use HasFactory<ProductionArtifactFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'width_mm' => 'decimal:2',
            'height_mm' => 'decimal:2',
            'back_transform' => PlateBackTransform::class,
            'metadata' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProductionJob, $this> */
    public function productionJob(): BelongsTo
    {
        return $this->belongsTo(ProductionJob::class);
    }

    /** @return BelongsTo<Plate, $this> */
    public function plate(): BelongsTo
    {
        return $this->belongsTo(Plate::class);
    }

    /** @return BelongsTo<PlateTemplateVersion, $this> */
    public function plateTemplateVersion(): BelongsTo
    {
        return $this->belongsTo(PlateTemplateVersion::class);
    }

    /**
     * @param  'front'|'back'  $face
     */
    public function content(string $face): string
    {
        $path = $face === 'front' ? $this->front_storage_path : $this->back_storage_path;
        $content = Storage::disk($this->disk())->get($path);

        abort_if($content === null, 404, 'El archivo de producción ya no existe en el almacenamiento.');

        return $content;
    }

    /**
     * @param  'front'|'back'  $face
     */
    public function sha256(string $face): string
    {
        return $face === 'front' ? $this->front_sha256 : $this->back_sha256;
    }

    public function disk(): string
    {
        return (string) config('finisher.production_artifact_disk', 'local');
    }
}
