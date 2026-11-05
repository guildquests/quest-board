type ComingSoonPanelProps = {
    title: string;
};

/** Placeholder content for pages whose feature is not built yet. */
export function ComingSoonPanel({
    title,
}: ComingSoonPanelProps): React.JSX.Element {
    return (
        <section className="rounded-[14px] border border-line bg-linear-to-b from-panel-2 to-panel p-8 text-center">
            <h2 className="font-brand text-3xl font-bold text-primary-soft">
                {title}
            </h2>
            <p className="mt-2 text-muted-foreground">Coming soon.</p>
        </section>
    );
}
