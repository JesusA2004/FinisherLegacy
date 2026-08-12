/**
 * Mirrors the element shape produced by PlateTemplateSeeder / consumed by
 * PlateTemplateRenderService (app/Services/PlateTemplateRenderService.php).
 * Keep both in sync — this is the one config shape used by editor, preview,
 * and export alike.
 */
export type PlateElementType =
    | 'static_text'
    | 'dynamic_text'
    | 'qr'
    | 'image'
    | 'logo'
    | 'icon'
    | 'line'
    | 'rect'
    | 'serial';

export interface PlateElement {
    id: string;
    type: PlateElementType;
    text?: string;
    field?: string;
    x_mm: number;
    y_mm: number;
    width_mm: number;
    height_mm: number;
    font_family?: string;
    font_size_pt?: number;
    font_weight?: number;
    text_align?: 'left' | 'center' | 'right';
    color?: string;
    auto_fit?: boolean;
    min_font_size_pt?: number;
    required?: boolean;
    error_correction?: 'L' | 'M' | 'Q' | 'H';
    src?: string;
    stroke?: string;
    fill?: string;
    stroke_width_mm?: number;
}

export type PlateFace = 'front' | 'back';
export type PlateRenderMode = 'product' | 'production';

export const TEXT_ELEMENT_TYPES: PlateElementType[] = [
    'static_text',
    'dynamic_text',
    'serial',
];

export function isTextElement(type: PlateElementType): boolean {
    return TEXT_ELEMENT_TYPES.includes(type);
}
