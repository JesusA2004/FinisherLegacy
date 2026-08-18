<?php

namespace App\Services;

use DOMDocument;

/**
 * A fixed 60x40mm calibration fixture for the physical laser test print
 * (§20-22 of the two-face production rules) — never tied to a real Plate or
 * Legacy Code. The QR codes point at a clearly-labeled test path, not a
 * real /l/{code}, so a sacrificial engraved sample can never be mistaken
 * for (or scanned into) a real Legacy.
 */
class CalibrationCardService
{
    private const SVG_NS = 'http://www.w3.org/2000/svg';

    private const WIDTH_MM = 60.0;

    private const HEIGHT_MM = 40.0;

    public function __construct(private readonly QrCodeService $qr) {}

    /**
     * Front test: several name lengths, small text sizes, and line
     * thicknesses — everything needed to judge legibility/kerf on a real
     * engrave before touching a production plate.
     */
    public function renderFront(): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $svg = $this->newSvg($doc);

        $this->text($doc, $svg, 3, 4, 'CALIBRACIÓN — PRUEBA DE GRABADO', 5, 700);
        $this->text($doc, $svg, 3, 9, 'FINISHER LEGACY · NO ES UNA PLACA REAL', 3, 400);

        $names = ['ZURIEL ÁVILA', 'MARÍA CARMEN ALANIZ GUTIÉRREZ', 'JOSÉ LUIS'];
        $y = 15;
        foreach ($names as $name) {
            $this->text($doc, $svg, 3, $y, $name, 4, 600);
            $y += 5;
        }

        // Small-text ladder: does 2mm still read cleanly on this material?
        $sizes = [2, 2.5, 3, 3.5, 4];
        $x = 3;
        foreach ($sizes as $ptSize) {
            $this->text($doc, $svg, $x, 33, $ptSize.'pt', $ptSize, 400);
            $x += 10;
        }

        // Line thickness ladder: fine / medium / thick.
        $this->line($doc, $svg, 3, 38, 15, 38, 0.15);
        $this->line($doc, $svg, 20, 38, 32, 38, 0.35);
        $this->line($doc, $svg, 37, 38, 49, 38, 0.6);

        return (string) $doc->saveXML($svg);
    }

    /**
     * Back test: the actual thing this whole print exists to answer — what
     * QR size is the smallest that still scans reliably off real steel?
     */
    public function renderBack(): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $svg = $this->newSvg($doc);

        $this->text($doc, $svg, 0, 3, 'FINISHER LEGACY — CALIBRACIÓN QR', 4, 700, 'middle', self::WIDTH_MM / 2);

        $sizes = [8, 10, 12, 14];
        $x = 3;
        foreach ($sizes as $sizeMm) {
            $this->qrBlock($doc, $svg, $x, 8, $sizeMm);
            $x += $sizeMm + 3;
        }

        $this->text($doc, $svg, 0, 34, 'FL-TEST0000 · NO ES UN LEGACY CODE REAL', 3.5, 600, 'middle', self::WIDTH_MM / 2);
        $this->text($doc, $svg, 0, 37.5, 'Escanea cada QR y marca el tamaño mínimo que funciona.', 2.6, 400, 'middle', self::WIDTH_MM / 2);

        return (string) $doc->saveXML($svg);
    }

    private function newSvg(DOMDocument $doc): \DOMElement
    {
        $svg = $doc->createElementNS(self::SVG_NS, 'svg');
        $svg->setAttribute('width', self::WIDTH_MM.'mm');
        $svg->setAttribute('height', self::HEIGHT_MM.'mm');
        $svg->setAttribute('viewBox', '0 0 '.self::WIDTH_MM.' '.self::HEIGHT_MM);
        $doc->appendChild($svg);

        $bg = $doc->createElement('rect');
        $bg->setAttribute('x', '0');
        $bg->setAttribute('y', '0');
        $bg->setAttribute('width', (string) self::WIDTH_MM);
        $bg->setAttribute('height', (string) self::HEIGHT_MM);
        $bg->setAttribute('fill', '#ffffff');
        $svg->appendChild($bg);

        return $svg;
    }

    private function text(DOMDocument $doc, \DOMElement $parent, float $x, float $y, string $content, float $sizeMm, int $weight, string $anchor = 'start', ?float $anchorX = null): void
    {
        $text = $doc->createElement('text');
        $text->setAttribute('x', (string) ($anchorX ?? $x));
        $text->setAttribute('y', (string) $y);
        $text->setAttribute('text-anchor', $anchor);
        $text->setAttribute('font-family', 'Inter, Arial, sans-serif');
        $text->setAttribute('font-size', (string) $sizeMm);
        $text->setAttribute('font-weight', (string) $weight);
        $text->setAttribute('fill', '#000000');
        $text->appendChild($doc->createTextNode($content));
        $parent->appendChild($text);
    }

    private function line(DOMDocument $doc, \DOMElement $parent, float $x1, float $y1, float $x2, float $y2, float $strokeWidth): void
    {
        $line = $doc->createElement('line');
        $line->setAttribute('x1', (string) $x1);
        $line->setAttribute('y1', (string) $y1);
        $line->setAttribute('x2', (string) $x2);
        $line->setAttribute('y2', (string) $y2);
        $line->setAttribute('stroke', '#000000');
        $line->setAttribute('stroke-width', (string) $strokeWidth);
        $parent->appendChild($line);
    }

    private function qrBlock(DOMDocument $doc, \DOMElement $parent, float $x, float $y, float $sizeMm): void
    {
        $qrSvgString = $this->qr->svg('finisherlegacy.local/laser-test?size='.$sizeMm.'mm', 240);
        $qrDoc = new DOMDocument;
        $qrDoc->loadXML($qrSvgString);
        $qrRoot = $qrDoc->documentElement;

        if ($qrRoot === null) {
            return;
        }

        $wrapper = $doc->createElementNS(self::SVG_NS, 'svg');
        $wrapper->setAttribute('x', (string) $x);
        $wrapper->setAttribute('y', (string) $y);
        $wrapper->setAttribute('width', (string) $sizeMm);
        $wrapper->setAttribute('height', (string) $sizeMm);
        $viewBox = $qrRoot->getAttribute('viewBox') ?: ('0 0 '.$qrRoot->getAttribute('width').' '.$qrRoot->getAttribute('height'));
        $wrapper->setAttribute('viewBox', $viewBox);
        $wrapper->setAttribute('preserveAspectRatio', 'xMidYMid meet');

        foreach (iterator_to_array($qrRoot->childNodes) as $child) {
            $wrapper->appendChild($doc->importNode($child, true));
        }

        $parent->appendChild($wrapper);

        $this->text($doc, $parent, 0, $y + $sizeMm + 3, $sizeMm.'mm', 2.6, 600, 'middle', $x + $sizeMm / 2);
    }
}
