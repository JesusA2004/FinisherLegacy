<?php

namespace App\Services;

use App\Models\Plate;
use App\Models\PlateTemplateVersion;
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
    public function exportFace(Plate $plate, string $face, string $format, string $mode = PlateTemplateRenderService::MODE_PRODUCTION, int $dpi = 300): array
    {
        $version = $this->requireVersion($plate);
        $data = PlateRenderData::fromPlate($plate);

        return match ($format) {
            'svg' => [
                'content' => $this->svgRenderer->renderSvg($version, $face, $data, $mode),
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
     * ZIP package: front.svg, back.svg, qr.svg, metadata.json — per plate/serial/.
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

        $zip->addFromString('front.svg', $this->svgRenderer->renderSvg($version, 'front', $data, PlateTemplateRenderService::MODE_PRODUCTION));
        $zip->addFromString('back.svg', $this->svgRenderer->renderSvg($version, 'back', $data, PlateTemplateRenderService::MODE_PRODUCTION));

        if ($plate->legacyCode) {
            $qrSvg = app(QrCodeService::class)->svg(route('legacy-code.show', $plate->legacyCode->code), 960);
            $zip->addFromString('qr.svg', $qrSvg);
        }

        $zip->addFromString('metadata.json', (string) json_encode([
            'serial' => $plate->serial_number,
            'legacy_code' => $plate->legacyCode?->code,
            'event' => $plate->eventEdition?->event->name ?? $plate->event_name,
            'template' => $plate->plateTemplate?->name,
            'template_version' => $version->version,
            'athlete' => $plate->athlete_name,
            'generated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $zip->close();

        $content = file_get_contents($tempPath);
        unlink($tempPath);

        return $content === false ? '' : $content;
    }

    /**
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

            $zip->addFromString("{$serial}-front.svg", $this->svgRenderer->renderSvg($version, 'front', $data, PlateTemplateRenderService::MODE_PRODUCTION));
            $zip->addFromString("{$serial}-back.svg", $this->svgRenderer->renderSvg($version, 'back', $data, PlateTemplateRenderService::MODE_PRODUCTION));
            $manifest[] = ['serial' => $serial, 'legacy_code' => $plate->legacyCode?->code, 'athlete' => $plate->athlete_name];
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
