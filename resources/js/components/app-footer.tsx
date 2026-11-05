import { usePage } from '@inertiajs/react';
import { Github } from 'lucide-react';

/** Small muted footer line linking to the public source repo; hidden when `repositoryUrl` is empty. */
export function AppFooter({
    className,
}: {
    className?: string;
}): React.JSX.Element | null {
    const { branding, repositoryUrl } = usePage().props;

    if (!repositoryUrl) {
        return null;
    }

    return (
        <footer
            className={`flex flex-wrap items-center justify-center gap-x-2 gap-y-1 px-4 text-center text-xs text-muted-foreground ${className ?? ''}`}
        >
            <span>{branding.name} is open source.</span>
            <a
                href={repositoryUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1 underline underline-offset-2 hover:text-foreground"
            >
                <Github aria-hidden className="size-3.5" />
                View it on GitHub
            </a>
        </footer>
    );
}
