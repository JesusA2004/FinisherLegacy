<?php

namespace App\Services;

use App\Models\PlateTemplateVersion;
use App\Support\PlateMeasurementService;
use App\Support\PlateRenderData;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

/**
 * PDF production file, sized to the plate's exact physical dimensions (no A4 page
 * with the plate floating on it). Reads the same resolved element list as the SVG
 * and PNG renderers; only the drawing step (HTML/CSS here, via dompdf) differs.
 */
class PdfExportService
{
    public function __construct(
        private readonly PlateTemplateRenderService $renderer,
        private readonly PlateMeasurementService $measure,
        private readonly QrCodeService $qr,
        private readonly GdPlateRenderer $gd,
    ) {}

    public function renderPdf(
        PlateTemplateVersion $version,
        string $face,
        PlateRenderData $data,
        string $mode = PlateTemplateRenderService::MODE_PRODUCTION,
    ): string {
        $template = $version->plateTemplate;
        $widthMm = (float) $template->width_mm;
        $heightMm = (float) $template->height_mm;
        $isProduction = $mode === PlateTemplateRenderService::MODE_PRODUCTION;

        $resolved = $this->renderer->resolveElements($version, $face, $data);
        $elements = array_map(fn (array $element) => $this->prepareElement($element, $isProduction), $resolved['elements']);

        $html = view('plates.face-pdf', [
            'widthMm' => $widthMm,
            'heightMm' => $heightMm,
            'elements' => $elements,
            'isProduction' => $isProduction,
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Inter');

        $dompdf = new Dompdf($options);
        $this->registerFonts($dompdf);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, $this->measure->mmToPt($widthMm), $this->measure->mmToPt($heightMm)]);
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * @param  array<string, mixed>  $element
     * @return array<string, mixed>
     */
    private function prepareElement(array $element, bool $isProduction): array
    {
        $type = $element['type'] ?? 'static_text';

        if ($type === 'qr' && ! empty($element['qr_content'])) {
            $png = $this->qr->png((string) $element['qr_content'], 480);
            $element['data_uri'] = 'data:image/png;base64,'.base64_encode($png);
        }

        if (in_array($type, ['image', 'logo', 'icon'], true) && ! empty($element['src']) && is_string($element['src'])) {
            if (Storage::disk('public')->exists($element['src'])) {
                $contents = Storage::disk('public')->get($element['src']);
                $mime = Storage::disk('public')->mimeType($element['src']) ?: 'image/png';
                $element['data_uri'] = "data:{$mime};base64,".base64_encode((string) $contents);
            }
        }

        // dompdf has no native support for the polylines/circles vector_icon
        // needs, so rasterize it once (same catalog GdPlateRenderer draws
        // for the PNG export) and embed it as a plain <img>.
        if ($type === 'vector_icon' && ! empty($element['icon'])) {
            $widthPx = max(1, (int) round((float) ($element['width_mm'] ?? 0) * 8));
            $heightPx = max(1, (int) round((float) ($element['height_mm'] ?? 0) * 8));
            $png = $this->gd->renderVectorIconPng($element, $widthPx, $heightPx, $isProduction);
            $element['data_uri'] = 'data:image/png;base64,'.base64_encode($png);
        }

        return $element;
    }

    private function registerFonts(Dompdf $dompdf): void
    {
        $fontMetrics = $dompdf->getFontMetrics();
        $regular = config('plate-studio.fonts.regular');
        $bold = config('plate-studio.fonts.bold');

        if (is_string($regular) && is_file($regular)) {
            $fontMetrics->registerFont(['family' => 'Inter', 'style' => 'normal', 'weight' => 'normal'], $regular);
        }

        if (is_string($bold) && is_file($bold)) {
            $fontMetrics->registerFont(['family' => 'Inter', 'style' => 'normal', 'weight' => 'bold'], $bold);
        }
    }
}
