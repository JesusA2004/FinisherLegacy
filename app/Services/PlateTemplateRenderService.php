<?php

namespace App\Services;

use App\Enums\PlateBackTransform;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
use App\Support\PlateMeasurementService;
use App\Support\PlateRenderData;
use App\Support\PlateVectorIcons;
use App\Support\TextFitService;
use DOMDocument;
use DOMElement;

/**
 * The single renderer every output reads from: web preview, SVG/PNG/PDF export, and
 * the calibration print all call resolveElements() for the same computed layout, then
 * only the final drawing step (SVG string here, GD in GdPlateRenderer, HTML in the
 * dompdf view) differs. Nothing recomputes positions or auto-fit independently.
 */
class PlateTemplateRenderService
{
    public const MODE_PRODUCT = 'product';

    public const MODE_PRODUCTION = 'production';

    private const SVG_NS = 'http://www.w3.org/2000/svg';

    public function __construct(
        private readonly PlateMeasurementService $measure,
        private readonly TextFitService $textFit,
        private readonly QrCodeService $qr,
        private readonly FontOutlineService $fontOutline,
    ) {}

    /**
     * @return array{elements: list<array<string, mixed>>, warnings: list<string>}
     */
    public function resolveElements(PlateTemplateVersion $version, string $face, PlateRenderData $data): array
    {
        $template = $version->plateTemplate;
        $config = $face === 'back' ? $version->back_configuration : $version->front_configuration;
        $elements = $config['elements'] ?? [];

        $resolved = [];
        $warnings = [];

        foreach ($elements as $element) {
            // e.g. 'visible_when' => 'swim_time' hides the whole element (its
            // label included) when that field is empty — a static_text like
            // "SWIM {{swim_time}}" must not render as a bare "SWIM" for a
            // runner with no swim split.
            if (! $this->isVisible($element, $data)) {
                continue;
            }

            [$out, $elementWarnings] = $this->resolveElement($element, $data, $template);
            $resolved[] = $out;
            array_push($warnings, ...$elementWarnings);
        }

        return ['elements' => $resolved, 'warnings' => array_values(array_unique($warnings))];
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function isVisible(array $element, PlateRenderData $data): bool
    {
        $field = $element['visible_when'] ?? null;

        if ($field === null || $field === '') {
            return true;
        }

        $value = $data->get((string) $field);

        return $value !== null && trim((string) $value) !== '';
    }

    /**
     * @param  array<string, mixed>  $element
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function resolveElement(array $element, PlateRenderData $data, PlateTemplate $template): array
    {
        $type = $element['type'] ?? 'static_text';
        $out = $element;
        $warnings = [];
        $label = $element['id'] ?? $type;

        if (in_array($type, ['static_text', 'dynamic_text', 'serial'], true)) {
            $rawText = $element['text'] ?? (isset($element['field']) ? '{{'.$element['field'].'}}' : '');
            $text = $this->interpolate((string) $rawText, $data);
            $out['resolved_text'] = $text;

            if (($element['required'] ?? false) && trim($text) === '') {
                $warnings[] = "Campo obligatorio vacío: {$label}";
            }

            $fontSize = (float) ($element['font_size_pt'] ?? 10);
            if (($element['auto_fit'] ?? false) && $text !== '' && (float) ($element['width_mm'] ?? 0) > 0) {
                $fit = $this->textFit->fit(
                    $text,
                    (float) $element['width_mm'],
                    $fontSize,
                    (float) ($element['min_font_size_pt'] ?? $fontSize),
                );
                $out['computed_font_size_pt'] = $fit['font_size_pt'];

                if (! $fit['fits']) {
                    $preview = mb_strimwidth($text, 0, 24, '…');
                    $warnings[] = "El texto \"{$preview}\" no cabe ni al tamaño mínimo configurado en \"{$label}\".";
                }
            } else {
                $out['computed_font_size_pt'] = $fontSize;
            }
        }

        if ($type === 'qr') {
            $widthMm = (float) ($element['width_mm'] ?? 0);
            $validatedMin = $template->minimum_validated_qr_size_mm !== null
                ? (float) $template->minimum_validated_qr_size_mm
                : null;

            if ($validatedMin !== null && $widthMm > 0 && $widthMm < $validatedMin) {
                $warnings[] = sprintf(
                    'El QR "%s" mide %.1fmm — por debajo de los %.1fmm validados físicamente para este molde.',
                    $label,
                    $widthMm,
                    $validatedMin,
                );
            } elseif ($validatedMin === null && $widthMm > 0 && $widthMm < 10) {
                $warnings[] = sprintf(
                    'El QR "%s" mide %.1fmm — mínimo operativo sugerido ~10-12mm, sin validar aún con una prueba física de grabado.',
                    $label,
                    $widthMm,
                );
            }

            $legacyCode = $data->get('legacy_code');
            $out['qr_content'] = $legacyCode ? url('/l/'.$legacyCode) : null;
        }

        $x = (float) ($element['x_mm'] ?? 0);
        $y = (float) ($element['y_mm'] ?? 0);
        $w = (float) ($element['width_mm'] ?? 0);
        $h = (float) ($element['height_mm'] ?? 0);
        $tw = (float) ($template->width_mm ?? 0);
        $th = (float) ($template->height_mm ?? 0);
        $margin = (float) ($template->safe_margin_mm ?? 0);

        if ($tw > 0 && $th > 0) {
            if ($x < 0 || $y < 0 || ($x + $w) > $tw || ($y + $h) > $th) {
                $warnings[] = "El elemento \"{$label}\" queda fuera del canvas.";
            } elseif ($margin > 0 && ($x < $margin || $y < $margin || ($x + $w) > ($tw - $margin) || ($y + $h) > ($th - $margin))) {
                $warnings[] = sprintf('El elemento "%s" invade el área de seguridad (%.1fmm).', $label, $margin);
            }
        }

        return [$out, $warnings];
    }

    public function interpolate(string $text, PlateRenderData $data): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-z_]+)\s*\}\}/i',
            fn (array $m) => $data->get($m[1]) ?? '',
            $text,
        );
    }

    /**
     * @return array{elements: list<array<string, mixed>>, warnings: list<string>}
     */
    public function warnings(PlateTemplateVersion $version, string $face, PlateRenderData $data): array
    {
        return $this->resolveElements($version, $face, $data);
    }

    /**
     * The back face's physical orientation in the jig (§25-28) only ever
     * applies to the actual production file — MODE_PRODUCTION — never to
     * the "how I designed it" preview Plate Studio shows by default. That
     * preview/production split doubles as "BACK ORIGINAL" vs "BACK PARA
     * PRODUCCIÓN": there's no separate toggle to build or keep in sync.
     */
    public function renderSvg(PlateTemplateVersion $version, string $face, PlateRenderData $data, string $mode = self::MODE_PRODUCT, bool $textAsPaths = false): string
    {
        $template = $version->plateTemplate;
        $tw = (float) $template->width_mm;
        $th = (float) $template->height_mm;
        $resolved = $this->resolveElements($version, $face, $data);
        $isProduction = $mode === self::MODE_PRODUCTION;
        // Text-as-paths only ever applies to the real production file — the
        // web preview always shows live <text> so it stays editable-looking
        // and never silently disagrees with the on-screen font.
        $outlineText = $isProduction && $textAsPaths && $this->fontOutline->isAvailable();

        $doc = new DOMDocument('1.0', 'UTF-8');
        $svg = $doc->createElementNS(self::SVG_NS, 'svg');
        $svg->setAttribute('width', $tw.'mm');
        $svg->setAttribute('height', $th.'mm');
        $svg->setAttribute('viewBox', "0 0 {$tw} {$th}");
        $doc->appendChild($svg);

        if ($isProduction) {
            $bg = $doc->createElement('rect');
            $bg->setAttribute('x', '0');
            $bg->setAttribute('y', '0');
            $bg->setAttribute('width', (string) $tw);
            $bg->setAttribute('height', (string) $th);
            $bg->setAttribute('fill', '#ffffff');
            $svg->appendChild($bg);
        }

        $container = $svg;

        // In-memory templates built for a live preview (not yet persisted —
        // e.g. PlateStudioController::preview()) never set back_transform,
        // so this defaults to None rather than erroring on a null enum.
        $backTransform = $template->back_transform ?? PlateBackTransform::None;

        if ($isProduction && $face === 'back' && $backTransform !== PlateBackTransform::None) {
            $container = $doc->createElementNS(self::SVG_NS, 'g');
            $container->setAttribute('transform', $this->backTransformMatrix($backTransform, $tw, $th));
            $svg->appendChild($container);
        }

        foreach ($resolved['elements'] as $element) {
            $node = $this->buildSvgElement($doc, $element, $isProduction, $outlineText);

            if ($node !== null) {
                $container->appendChild($node);
            }
        }

        return (string) $doc->saveXML($svg);
    }

    /**
     * SVG transform, not a CSS one — applies in the same user-space units
     * (mm) the rest of the document is drawn in, and keeps width/height
     * untouched (§27: the physical plate size never changes, only what's
     * drawn on it flips/rotates).
     */
    private function backTransformMatrix(PlateBackTransform $transform, float $width, float $height): string
    {
        return match ($transform) {
            PlateBackTransform::MirrorX => "translate({$this->fmt($width)},0) scale(-1,1)",
            PlateBackTransform::MirrorY => "translate(0,{$this->fmt($height)}) scale(1,-1)",
            PlateBackTransform::Rotate180 => "rotate(180,{$this->fmt($width / 2)},{$this->fmt($height / 2)})",
            PlateBackTransform::None => '',
        };
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function buildSvgElement(DOMDocument $doc, array $element, bool $isProduction, bool $outlineText = false): ?DOMElement
    {
        return match ($element['type'] ?? 'static_text') {
            'static_text', 'dynamic_text', 'serial' => $this->buildTextNode($doc, $element, $isProduction, $outlineText),
            'line' => $this->buildLineNode($doc, $element, $isProduction),
            'rect' => $this->buildRectNode($doc, $element, $isProduction),
            'qr' => $this->buildQrNode($doc, $element),
            'image', 'logo', 'icon' => $this->buildImageNode($doc, $element, $isProduction),
            'vector_icon' => $this->buildVectorIconNode($doc, $element, $isProduction),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function buildVectorIconNode(DOMDocument $doc, array $element, bool $isProduction): ?DOMElement
    {
        $iconId = (string) ($element['icon'] ?? '');
        $shapes = PlateVectorIcons::shapesFor($iconId);

        if ($shapes === []) {
            return null;
        }

        $x = (float) ($element['x_mm'] ?? 0);
        $y = (float) ($element['y_mm'] ?? 0);
        $w = (float) ($element['width_mm'] ?? 0);
        $h = (float) ($element['height_mm'] ?? 0);
        $color = $isProduction ? '#000000' : (string) ($element['stroke'] ?? '#000000');
        $strokeWidth = (float) ($element['stroke_width_mm'] ?? 0.4);

        $group = $doc->createElementNS(self::SVG_NS, 'g');
        $group->setAttribute('stroke', $color);
        $group->setAttribute('stroke-width', $this->fmt($strokeWidth));
        $group->setAttribute('fill', 'none');
        $group->setAttribute('stroke-linecap', 'round');
        $group->setAttribute('stroke-linejoin', 'round');

        foreach ($shapes as $shape) {
            if ($shape['type'] === 'circle') {
                $circle = $doc->createElement('circle');
                $circle->setAttribute('cx', $this->fmt($x + $shape['cx'] * $w));
                $circle->setAttribute('cy', $this->fmt($y + $shape['cy'] * $h));
                $circle->setAttribute('r', $this->fmt($shape['r'] * min($w, $h)));
                $group->appendChild($circle);

                continue;
            }

            $points = array_map(
                fn (array $p) => $this->fmt($x + $p[0] * $w).','.$this->fmt($y + $p[1] * $h),
                $shape['points'],
            );
            $polyline = $doc->createElement('polyline');
            $polyline->setAttribute('points', implode(' ', $points));
            $group->appendChild($polyline);
        }

        return $group;
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function buildTextNode(DOMDocument $doc, array $element, bool $isProduction, bool $outlineText = false): DOMElement
    {
        $x = (float) ($element['x_mm'] ?? 0);
        $y = (float) ($element['y_mm'] ?? 0);
        $w = (float) ($element['width_mm'] ?? 0);
        $h = (float) ($element['height_mm'] ?? 0);
        $align = $element['text_align'] ?? 'left';
        $anchor = match ($align) {
            'center' => 'middle',
            'right' => 'end',
            default => 'start',
        };
        $tx = match ($align) {
            'center' => $x + $w / 2,
            'right' => $x + $w,
            default => $x,
        };
        $ty = $y + $h / 2;

        $resolvedText = (string) ($element['resolved_text'] ?? '');
        $fontSizePt = (float) ($element['computed_font_size_pt'] ?? $element['font_size_pt'] ?? 10);
        $fontWeight = (int) ($element['font_weight'] ?? 400);
        $fill = $isProduction ? '#000000' : (string) ($element['color'] ?? '#000000');

        if ($outlineText) {
            $group = $this->fontOutline->buildTextPaths(
                $doc,
                $resolvedText,
                $x,
                $y,
                $w,
                $h,
                $anchor,
                $this->measure->ptToMm($fontSizePt),
                $fontWeight >= 600,
                $fill,
            );

            if ($group !== null) {
                return $group;
            }

            // A glyph this font genuinely doesn't have (e.g. an unusual
            // symbol) — fall through to a normal <text> node rather than
            // silently dropping the element from the export.
        }

        $text = $doc->createElement('text');
        $text->setAttribute('x', $this->fmt($tx));
        $text->setAttribute('y', $this->fmt($ty));
        $text->setAttribute('text-anchor', $anchor);
        $text->setAttribute('dominant-baseline', 'central');
        $fontFamily = $element['font_family'] ?? 'Inter';
        $text->setAttribute('font-family', "{$fontFamily}, Arial, sans-serif");
        $text->setAttribute('font-size', $this->fmt($this->measure->ptToMm($fontSizePt)));
        $text->setAttribute('font-weight', (string) $fontWeight);
        $text->setAttribute('fill', $fill);
        $text->appendChild($doc->createTextNode($resolvedText));

        return $text;
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function buildLineNode(DOMDocument $doc, array $element, bool $isProduction): DOMElement
    {
        $line = $doc->createElement('line');
        $x = (float) ($element['x_mm'] ?? 0);
        $y = (float) ($element['y_mm'] ?? 0);
        $line->setAttribute('x1', $this->fmt($x));
        $line->setAttribute('y1', $this->fmt($y));
        $line->setAttribute('x2', $this->fmt($x + (float) ($element['width_mm'] ?? 0)));
        $line->setAttribute('y2', $this->fmt($y + (float) ($element['height_mm'] ?? 0)));
        $line->setAttribute('stroke', $isProduction ? '#000000' : ($element['stroke'] ?? '#000000'));
        $line->setAttribute('stroke-width', $this->fmt((float) ($element['stroke_width_mm'] ?? 0.2)));

        return $line;
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function buildRectNode(DOMDocument $doc, array $element, bool $isProduction): DOMElement
    {
        $rect = $doc->createElement('rect');
        $rect->setAttribute('x', $this->fmt((float) ($element['x_mm'] ?? 0)));
        $rect->setAttribute('y', $this->fmt((float) ($element['y_mm'] ?? 0)));
        $rect->setAttribute('width', $this->fmt((float) ($element['width_mm'] ?? 0)));
        $rect->setAttribute('height', $this->fmt((float) ($element['height_mm'] ?? 0)));
        $rect->setAttribute('fill', $isProduction ? 'none' : ($element['fill'] ?? 'none'));
        $rect->setAttribute('stroke', $isProduction ? '#000000' : ($element['stroke'] ?? '#000000'));
        $rect->setAttribute('stroke-width', $this->fmt((float) ($element['stroke_width_mm'] ?? 0.2)));

        return $rect;
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function buildQrNode(DOMDocument $doc, array $element): ?DOMElement
    {
        $content = $element['qr_content'] ?? null;

        if (! $content) {
            return null;
        }

        $qrSvgString = $this->qr->svg((string) $content, 240);
        $qrDoc = new DOMDocument;
        $qrDoc->loadXML($qrSvgString);
        $qrRoot = $qrDoc->documentElement;

        if ($qrRoot === null) {
            return null;
        }

        $wrapper = $doc->createElementNS(self::SVG_NS, 'svg');
        $wrapper->setAttribute('x', $this->fmt((float) ($element['x_mm'] ?? 0)));
        $wrapper->setAttribute('y', $this->fmt((float) ($element['y_mm'] ?? 0)));
        $wrapper->setAttribute('width', $this->fmt((float) ($element['width_mm'] ?? 0)));
        $wrapper->setAttribute('height', $this->fmt((float) ($element['height_mm'] ?? 0)));
        $viewBox = $qrRoot->getAttribute('viewBox') ?: ('0 0 '.$qrRoot->getAttribute('width').' '.$qrRoot->getAttribute('height'));
        $wrapper->setAttribute('viewBox', $viewBox);
        $wrapper->setAttribute('preserveAspectRatio', 'xMidYMid meet');

        foreach (iterator_to_array($qrRoot->childNodes) as $child) {
            $wrapper->appendChild($doc->importNode($child, true));
        }

        return $wrapper;
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function buildImageNode(DOMDocument $doc, array $element, bool $isProduction): ?DOMElement
    {
        $src = $element['src'] ?? null;

        if (! $src) {
            return null;
        }

        $image = $doc->createElement('image');
        $image->setAttribute('x', $this->fmt((float) ($element['x_mm'] ?? 0)));
        $image->setAttribute('y', $this->fmt((float) ($element['y_mm'] ?? 0)));
        $image->setAttribute('width', $this->fmt((float) ($element['width_mm'] ?? 0)));
        $image->setAttribute('height', $this->fmt((float) ($element['height_mm'] ?? 0)));
        $image->setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', (string) $src);

        if ($isProduction) {
            $image->setAttribute('style', 'filter: grayscale(1) contrast(1000%);');
        }

        return $image;
    }

    private function fmt(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.') ?: '0';
    }
}
