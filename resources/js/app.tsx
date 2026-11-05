import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import DocsLayout from '@/layouts/docs-layout';
import PublicLayout from '@/layouts/public-layout';
import SettingsLayout from '@/layouts/settings/layout';

createInertiaApp({
    // The brand name is rendered into <meta name="application-name"> by app.blade.php; branding changes reload the page.
    title: (title) => {
        const appName =
            document.querySelector<HTMLMetaElement>(
                'meta[name="application-name"]',
            )?.content ?? '';

        return title ? `${title} - ${appName}` : appName;
    },
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('docs/'):
                return DocsLayout;
            case name.startsWith('public/'):
                return PublicLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: 'var(--color-primary)',
    },
});
