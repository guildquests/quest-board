/** A theme preset from config('branding.presets'), as sent by BrandingController::edit. */
export type ThemePreset = {
    key: string;
    label: string;
    /** Colour tokens by name (`bg`, `primary`, ...); each one is the CSS variable `--color-<name>`. */
    colors: Record<string, string>;
};
