<?php

namespace App\Services;

use App\Models\Plate;
use App\Models\PlateTemplateVersion;
use App\Support\PlateFilename;
use App\Support\PlateRenderData;
use RuntimeException;
use ZipArchive;

/**
 * The only place production files (SVG/PNG/PDF/ZIP package) are produced from a real
 * Plate. Nothing is cached to disk — every call regenerates from the plate's frozen
 * snapshot + the exact template version it was created with, so "regenerate files"
 * (never a new Legacy Code, see PlateGenerationService) is always correct by
 * construction rather than something that can go stale.
 */
class PlateExportService
{
    public function __construct(
        private readonly PlateTemplateRenderService $svgRenderer,
        private readonly GdPlateRenderer $pngRenderer,
        private readonly PdfExportService $pdfRenderer,
    ) {}

    /**
     * @return array{content: string, mime: string, extension: string}
     */
    public function exportFace(Plate $plate, string $face, string $format, string $mode = PlateTemplateRenderService::MODE_PRODUCTION, int $dpi = 300, bool $textAsPaths = false): array
    {
        $version = $this->requireVersion($plate);
        $data = PlateRenderData::fromPlate($plate);

        return match ($format) {
            'svg' => [
                'content' => $this->svgRenderer->renderSvg($version, $face, $data, $mode, $textAsPaths),
                'mime' => 'image/svg+xml',
                'extension' => 'svg',
            ],
            'png' => [
                'content' => $this->pngRenderer->renderPng($version, $face, $data, $mode, $dpi),
                'mime' => 'image/png',
                'extension' => 'png',
            ],
            'pdf' => [
                'content' => $this->pdfRenderer->renderPdf($version, $face, $data, $mode),
                'mime' => 'application/pdf',
                'extension' => 'pdf',
            ],
            default => throw new RuntimeException("Unsupported export format [{$format}]."),
        };
    }

    /**
     * @return array{content: string, mime: string, extension: string}
     */
    public function exportQr(Plate $plate, string $format = 'svg'): array
    {
        $plate->loadMissing('legacyCode');
        abort_if(! $plate->legacyCode, 422, 'Esta placa no tiene un Legacy Code asignado.');

        $qr = app(QrCodeService::class);
        $url = route('legacy-code.show', $plate->legacyCode->code);

        return $format === 'png'
            ? ['content' => $qr->png($url, 960), 'mime' => 'image/png', 'extension' => 'png']
            : ['content' => $qr->svg($url, 960), 'mime' => 'image/svg+xml', 'extension' => 'svg'];
    }

    /**
     * ZIP package for one plate — always both faces, every format, so a
     * production operator never has to come back for a file they forgot:
     * plate-{serial}/front.{svg,png,pdf}, back.{svg,png,pdf}, qr.svg, production.json.
     */
    public function exportPackage(Plate $plate): string
    {
        $version = $this->requireVersion($plate);
        $data = PlateRenderData::fromPlate($plate);
        $plate->loadMissing(['legacyCode', 'eventEdition.event', 'plateTemplate']);

        $tempPath = tempnam(sys_get_temp_dir(), 'fl-plate-');

        if ($tempPath === false) {
            throw new RuntimeException('Unable to create a temporary file for the export package.');
        }

        $zip = new ZipArchive;
        $zip->open($tempPath, ZipArchive::OVERWRITE);
        $root = 'plate-'.$plate->serial_number.'/';

        foreach (['front', 'back'] as $face) {
            $zip->addFromString($root."{$face}.svg", $this->svgRenderer->renderSvg($version, $face, $data, PlateTemplateRenderService::MODE_PRODUCTION));
            $zip->addFromString($root."{$face}.png", $this->pngRenderer->renderPng($version, $face, $data, PlateTemplateRenderService::MODE_PRODUCTION, 300));
            $zip->addFromString($root."{$face}.pdf", $this->pdfRenderer->renderPdf($version, $face, $data, PlateTemplateRenderService::MODE_PRODUCTION));
        }

        if ($plate->legacyCode) {
            $qrSvg = app(QrCodeService::class)->svg(route('legacy-code.show', $plate->legacyCode->code), 960);
            $zip->addFromString($root.'qr.svg', $qrSvg);
        }

        $template = $plate->plateTemplate;

        $zip->addFromString($root.'production.json', (string) json_encode([
            'serial' => $plate->serial_number,
            'legacy_code' => $plate->legacyCode?->code,
            'event' => $plate->eventEdition?->event->name ?? $plate->event_name,
            'template' => $template?->name,
            'template_version' => $version->version,
            'width_mm' => $template !== null ? (float) $template->width_mm : null,
            'height_mm' => $template !== null ? (float) $template->height_mm : null,
            'back_transform' => $template?->back_transform->value,
            'faces' => ['front', 'back'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $zip->close();

        $content = file_get_contents($tempPath);
        unlink($tempPath);

        return $content === false ? '' : $content;
    }

    /**
     * Batch ZIP for production — flat, sanitized `{bib-or-serial}_{NAME}_{FACE}.svg`
     * filenames so an operator can tell plates apart at a glance on a shop-floor
     * PC without opening each one; no email or other PII in the name.
     *
     * @param  iterable<Plate>  $plates
     */
    public function exportBatch(iterable $plates): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'fl-batch-');

        if ($tempPath === false) {
            throw new RuntimeException('Unable to create a temporary file for the batch export.');
        }

        $zip = new ZipArchive;
        $zip->open($tempPath, ZipArchive::OVERWRITE);
        $manifest = [];

        foreach ($plates as $plate) {
            $version = $plate->plateTemplateVersion;

            if (! $version) {
                continue;
            }

            $data = PlateRenderData::fromPlate($plate);
            $serial = $plate->serial_number;
            $prefix = PlateFilename::batchPrefix($plate->athlete_name, $plate->bib_number, $serial);

            $zip->addFromString("{$prefix}_FRONT.svg", $this->svgRenderer->renderSvg($version, 'front', $data, PlateTemplateRenderService::MODE_PRODUCTION));
            $zip->addFromString("{$prefix}_BACK.svg", $this->svgRenderer->renderSvg($version, 'back', $data, PlateTemplateRenderService::MODE_PRODUCTION));
            $manifest[] = [
                'serial' => $serial,
                'legacy_code' => $plate->legacyCode?->code,
                'front_file' => "{$prefix}_FRONT.svg",
                'back_file' => "{$prefix}_BACK.svg",
            ];
        }

        $zip->addFromString('manifest.json', (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->close();

        $content = file_get_contents($tempPath);
        unlink($tempPath);

        return $content === false ? '' : $content;
    }

    private function requireVersion(Plate $plate): PlateTemplateVersion
    {
        $plate->loadMissing('plateTemplateVersion.plateTemplate');
        $version = $plate->plateTemplateVersion;

        abort_if(! $version, 422, 'Esta placa no tiene un molde asignado — no se puede generar el archivo de producción.');

        return $version;
    }
}
