<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; size: {{ $widthMm }}mm {{ $heightMm }}mm; }
    * { box-sizing: border-box; }
    html, body {
        margin: 0;
        padding: 0;
        width: {{ $widthMm }}mm;
        height: {{ $heightMm }}mm;
        font-family: 'Inter', sans-serif;
        background: #ffffff;
    }
    .plate { position: relative; width: {{ $widthMm }}mm; height: {{ $heightMm }}mm; }
    .el { position: absolute; }
    .el-text {
        white-space: nowrap;
        overflow: hidden;
    }
</style>
</head>
<body>
    <div class="plate">
        @foreach ($elements as $element)
            @php
                $type = $element['type'] ?? 'static_text';
                $x = (float) ($element['x_mm'] ?? 0);
                $y = (float) ($element['y_mm'] ?? 0);
                $w = (float) ($element['width_mm'] ?? 0);
                $h = (float) ($element['height_mm'] ?? 0);
            @endphp

            @if (in_array($type, ['static_text', 'dynamic_text', 'serial'], true))
                @php
                    $text = (string) ($element['resolved_text'] ?? '');
                @endphp
                @if ($text !== '')
                    @php
                        $fontSizePt = $element['computed_font_size_pt'] ?? $element['font_size_pt'] ?? 10;
                        $align = $element['text_align'] ?? 'left';
                        $weight = (int) ($element['font_weight'] ?? 400);
                        $color = $isProduction ? '#000000' : ($element['color'] ?? '#000000');
                    @endphp
                    <div
                        class="el el-text"
                        style="left:{{ $x }}mm; top:{{ $y }}mm; width:{{ $w }}mm; height:{{ $h }}mm;
                               font-size:{{ $fontSizePt }}pt; text-align:{{ $align }}; font-weight:{{ $weight }};
                               color:{{ $color }}; line-height:{{ $h }}mm;"
                    >{{ $text }}</div>
                @endif
            @elseif ($type === 'line')
                @php
                    $color = $isProduction ? '#000000' : ($element['stroke'] ?? '#000000');
                    $thickness = $element['stroke_width_mm'] ?? 0.2;
                @endphp
                <div class="el" style="left:{{ $x }}mm; top:{{ $y }}mm; width:{{ $w }}mm; border-top:{{ $thickness }}mm solid {{ $color }};"></div>
            @elseif ($type === 'rect')
                @php
                    $stroke = $isProduction ? '#000000' : ($element['stroke'] ?? '#000000');
                    $fill = $isProduction ? 'none' : ($element['fill'] ?? 'none');
                    $thickness = $element['stroke_width_mm'] ?? 0.2;
                @endphp
                <div
                    class="el"
                    style="left:{{ $x }}mm; top:{{ $y }}mm; width:{{ $w }}mm; height:{{ $h }}mm;
                           border:{{ $thickness }}mm solid {{ $stroke }};
                           background:{{ $fill === 'none' || $fill === '' ? 'transparent' : $fill }};"
                ></div>
            @elseif ($type === 'qr')
                @if (! empty($element['data_uri']))
                    <img class="el" style="left:{{ $x }}mm; top:{{ $y }}mm; width:{{ $w }}mm; height:{{ $h }}mm;" src="{{ $element['data_uri'] }}" alt="QR">
                @endif
            @elseif (in_array($type, ['image', 'logo', 'icon', 'vector_icon'], true))
                @if (! empty($element['data_uri']))
                    <img class="el" style="left:{{ $x }}mm; top:{{ $y }}mm; width:{{ $w }}mm; height:{{ $h }}mm;" src="{{ $element['data_uri'] }}" alt="">
                @endif
            @endif
        @endforeach
    </div>
</body>
</html>
