import { Form, Head, usePage } from '@inertiajs/react';
import { Check, Palette, Save } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import BrandingController from '@/actions/App/Http/Controllers/Settings/BrandingController';
import { SectionHeading } from '@/components/game/section-heading';
import InputError from '@/components/input-error';
import { ThemeSwatch } from '@/components/settings/theme-swatch';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fieldInputClassName, fieldLabelClassName } from '@/lib/form-styles';
import type { ThemePreset } from '@/types';

type BrandingImageName =
    | 'logo'
    | 'banner'
    | 'favicon'
    | 'header'
    | 'mascot_login'
    | 'mascot_empty'
    | 'mascot_reward';

type BrandingPageProps = {
    presets: ThemePreset[];
    uploaded: Record<BrandingImageName, boolean>;
    builtIn: Record<BrandingImageName, string | null>;
};

type ImageFieldProps = {
    name: BrandingImageName;
    label: string;
    hint: string;
    currentUrl: string | null;
    uploaded: boolean;
    builtInUrl: string | null;
    /** Shown when nothing has been uploaded yet. */
    fallbackNote: string;
    error?: string;
    wide?: boolean;
    tile?: boolean;
};

/** A file input that previews the chosen image before it is saved. */
function ImageField({
    name,
    label,
    hint,
    currentUrl,
    uploaded,
    builtInUrl,
    fallbackNote,
    error,
    wide = false,
    tile = false,
}: ImageFieldProps): React.JSX.Element {
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [useBuiltIn, setUseBuiltIn] = useState(false);
    const fileInput = useRef<HTMLInputElement>(null);

    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    const shown = useBuiltIn ? builtInUrl : (previewUrl ?? currentUrl);

    return (
        <div className="grid min-w-0 gap-[7px]">
            <Label htmlFor={name} className={fieldLabelClassName}>
                {label}
            </Label>
            <div
                className={
                    tile
                        ? 'grid gap-3'
                        : 'flex items-center gap-4 max-[520px]:flex-col max-[520px]:items-start'
                }
            >
                <div
                    className={
                        wide
                            ? 'h-16 w-40 shrink-0 overflow-hidden rounded-[10px] border border-line bg-bg'
                            : tile
                              ? 'h-28 w-full overflow-hidden rounded-[10px] border border-line bg-bg'
                              : 'size-16 shrink-0 overflow-hidden rounded-[10px] border border-line bg-bg'
                    }
                >
                    {shown ? (
                        <img
                            src={shown}
                            alt=""
                            className={
                                wide
                                    ? 'size-full object-cover'
                                    : 'size-full object-contain'
                            }
                        />
                    ) : null}
                </div>
                <div className="grid min-w-0 flex-1 gap-1">
                    <Input
                        ref={fileInput}
                        id={name}
                        name={name}
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        aria-describedby={`${name}-hint`}
                        className={fieldInputClassName}
                        onChange={(event) => {
                            const file = event.target.files?.[0];
                            setPreviewUrl(
                                file ? URL.createObjectURL(file) : null,
                            );

                            if (file) {
                                setUseBuiltIn(false);
                            }
                        }}
                    />
                    <small
                        id={`${name}-hint`}
                        className="text-[12.5px] text-muted-foreground"
                    >
                        {hint} {currentUrl ? '' : fallbackNote}
                    </small>
                    {uploaded && (
                        <label className="mt-1 inline-flex items-center gap-2 text-[12.5px] font-medium text-muted-foreground">
                            <Checkbox
                                name={`remove_${name}`}
                                value="1"
                                checked={useBuiltIn}
                                onCheckedChange={(checked) => {
                                    setUseBuiltIn(checked === true);

                                    // Drop a chosen file, so what is previewed is what gets saved.
                                    if (checked === true && fileInput.current) {
                                        fileInput.current.value = '';
                                        setPreviewUrl(null);
                                    }
                                }}
                                className="size-[17px] border-line data-[state=checked]:border-accent data-[state=checked]:bg-accent data-[state=checked]:text-bg"
                            />
                            Use the built-in image
                        </label>
                    )}
                </div>
            </div>
            <InputError message={error} />
        </div>
    );
}

export default function Branding({
    presets,
    uploaded,
    builtIn,
}: BrandingPageProps) {
    const { branding } = usePage().props;
    const [preset, setPreset] = useState(branding.theme_preset);

    // Preview the chosen preset on the whole page while picking; the saved page reloads with the real colours.
    useEffect(() => {
        const chosen = presets.find((item) => item.key === preset);

        if (!chosen || preset === branding.theme_preset) {
            return;
        }

        const root = document.documentElement;

        for (const [token, value] of Object.entries(chosen.colors)) {
            root.style.setProperty(`--color-${token}`, value);
        }

        return () => {
            for (const token of Object.keys(chosen.colors)) {
                root.style.removeProperty(`--color-${token}`);
            }
        };
    }, [preset, presets, branding.theme_preset]);

    return (
        <>
            <Head title="Branding" />

            <SectionHeading
                title="Branding"
                subtitle="The name, images and colours your team sees."
                icon={Palette}
                tone="primary"
            />

            <Form
                {...BrandingController.update.form()}
                options={{ preserveScroll: true }}
                // Colours are set in the page head, so a full reload is how a new look shows up.
                onSuccess={() => window.location.reload()}
                className="grid max-w-[900px] gap-4"
            >
                {({ processing, errors, recentlySuccessful }) => (
                    <>
                        <section className="grid gap-4 rounded-2xl border border-line bg-panel p-[22px] max-[520px]:p-4">
                            <h3 className="font-title text-lg font-bold text-foreground">
                                Name and tagline
                            </h3>
                            <div className="grid gap-4 min-[640px]:grid-cols-2">
                                <div className="grid gap-[7px]">
                                    <Label
                                        htmlFor="app_name"
                                        className={fieldLabelClassName}
                                    >
                                        App name
                                    </Label>
                                    <Input
                                        id="app_name"
                                        name="app_name"
                                        defaultValue={branding.name}
                                        required
                                        maxLength={60}
                                        className={fieldInputClassName}
                                    />
                                    <InputError message={errors.app_name} />
                                </div>

                                <div className="grid gap-[7px]">
                                    <Label
                                        htmlFor="tagline"
                                        className={fieldLabelClassName}
                                    >
                                        Tagline
                                    </Label>
                                    <Input
                                        id="tagline"
                                        name="tagline"
                                        defaultValue={branding.tagline}
                                        maxLength={120}
                                        className={fieldInputClassName}
                                    />
                                    <InputError message={errors.tagline} />
                                </div>
                            </div>
                        </section>

                        <section className="grid gap-4 rounded-2xl border border-line bg-panel p-[22px] max-[520px]:p-4">
                            <h3 className="font-title text-lg font-bold text-foreground">
                                Images
                            </h3>
                            <div className="grid gap-4 min-[640px]:grid-cols-2">
                                <ImageField
                                    name="logo"
                                    label="Logo"
                                    hint="PNG, JPG or WebP, up to 2 MB. A square image with a clear background works best."
                                    currentUrl={branding.logo_url}
                                    uploaded={uploaded.logo}
                                    builtInUrl={builtIn.logo}
                                    fallbackNote=""
                                    error={errors.logo}
                                />
                                <ImageField
                                    name="favicon"
                                    label="Favicon"
                                    hint="PNG, JPG or WebP, up to 512 KB. The small icon in the browser tab."
                                    currentUrl={branding.favicon_url}
                                    uploaded={uploaded.favicon}
                                    builtInUrl={builtIn.favicon}
                                    fallbackNote="Using the built-in icon."
                                    error={errors.favicon}
                                />
                            </div>
                            <ImageField
                                name="header"
                                label="Header image"
                                hint="PNG, JPG or WebP, up to 4 MB. Shown behind the app name at the top of every page and on the login page."
                                currentUrl={branding.header_url}
                                uploaded={uploaded.header}
                                builtInUrl={builtIn.header}
                                fallbackNote=""
                                error={errors.header}
                                wide
                            />
                            <ImageField
                                name="banner"
                                label="Banner"
                                hint="PNG, JPG or WebP, up to 4 MB. Shown behind the latest completion on the board."
                                currentUrl={branding.banner_url}
                                uploaded={uploaded.banner}
                                builtInUrl={builtIn.banner}
                                fallbackNote=""
                                error={errors.banner}
                                wide
                            />
                        </section>

                        <section className="grid gap-4 rounded-2xl border border-line bg-panel p-[22px] max-[520px]:p-4">
                            <h3 className="font-title text-lg font-bold text-foreground">
                                Mascot
                            </h3>
                            <div className="grid gap-4 min-[760px]:grid-cols-3">
                                <ImageField
                                    name="mascot_login"
                                    label="Login"
                                    hint="PNG, JPG or WebP, up to 2 MB. Shown on the login page."
                                    currentUrl={branding.mascots.login}
                                    uploaded={uploaded.mascot_login}
                                    builtInUrl={builtIn.mascot_login}
                                    fallbackNote=""
                                    error={errors.mascot_login}
                                    tile
                                />
                                <ImageField
                                    name="mascot_empty"
                                    label="Empty states"
                                    hint="PNG, JPG or WebP, up to 2 MB. Shown when a list has no items."
                                    currentUrl={branding.mascots.empty}
                                    uploaded={uploaded.mascot_empty}
                                    builtInUrl={builtIn.mascot_empty}
                                    fallbackNote=""
                                    error={errors.mascot_empty}
                                    tile
                                />
                                <ImageField
                                    name="mascot_reward"
                                    label="Reward"
                                    hint="PNG, JPG or WebP, up to 2 MB. Shown after a reward and on the board banner."
                                    currentUrl={branding.mascots.reward}
                                    uploaded={uploaded.mascot_reward}
                                    builtInUrl={builtIn.mascot_reward}
                                    fallbackNote=""
                                    error={errors.mascot_reward}
                                    tile
                                />
                            </div>
                        </section>

                        <section className="grid gap-4 rounded-2xl border border-line bg-panel p-[22px] max-[520px]:p-4">
                            <h3 className="font-title text-lg font-bold text-foreground">
                                Colour theme
                            </h3>
                            <fieldset className="grid gap-[7px]">
                                <legend className="sr-only">
                                    Colour theme
                                </legend>
                                <div className="grid grid-cols-2 gap-2.5 max-[420px]:grid-cols-1">
                                    {presets.map((item) => (
                                        <ThemeSwatch
                                            key={item.key}
                                            preset={item}
                                            name="theme_preset"
                                            selected={preset === item.key}
                                            onSelect={setPreset}
                                        />
                                    ))}
                                </div>
                                <small className="text-[12.5px] text-muted-foreground">
                                    The page previews your pick right away. Save
                                    to keep it for everyone.
                                </small>
                                <InputError message={errors.theme_preset} />
                            </fieldset>
                        </section>

                        <div className="sticky bottom-0 z-10 -mx-1 flex items-center justify-end gap-3 rounded-2xl border border-line bg-panel/95 px-[22px] py-[14px] shadow-[0_-8px_24px_rgb(0_0_0/0.18)] backdrop-blur-md max-[520px]:mx-0 max-[520px]:px-4">
                            {recentlySuccessful && (
                                <span
                                    role="status"
                                    className="mr-auto inline-flex items-center gap-1.5 text-sm font-semibold text-accent"
                                >
                                    <Check aria-hidden className="size-4" />
                                    Saved
                                </span>
                            )}
                            <Button
                                disabled={processing}
                                className="h-auto px-[22px] py-[11px] font-title text-lg font-bold"
                                data-test="update-branding-button"
                            >
                                <Save aria-hidden />
                                Save
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}
