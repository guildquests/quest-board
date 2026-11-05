import { Link, router } from '@inertiajs/react';
import { LogOut, Settings } from 'lucide-react';
import { Portrait } from '@/components/game/portrait';
import { XpBar } from '@/components/game/xp-bar';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';

type PlayerCardProps = {
    name: string;
    portrait?: string;
    level: number;
    title: string;
    xp: number;
    levelFloor: number;
    nextLevelXp: number | null;
    className?: string;
};

const actionClasses =
    'grid size-[34px] place-items-center rounded-[10px] border border-line bg-bg/60 text-muted-foreground transition-colors hover:border-primary/60 hover:text-primary-soft [&_svg]:size-[18px]';

export function PlayerCard({
    name,
    portrait,
    level,
    title,
    xp,
    levelFloor,
    nextLevelXp,
    className,
}: PlayerCardProps): React.JSX.Element {
    return (
        <div
            className={cn(
                'flex min-w-0 items-center gap-3.5 rounded-[14px] border border-primary/45 bg-linear-to-b from-panel-2/92 to-panel/94 py-3 pr-4.5 pl-3 backdrop-blur-md sm:min-w-[300px]',
                className,
            )}
        >
            <Portrait src={portrait} name={name} size="md" />

            <div className="min-w-0 flex-1">
                <strong className="block truncate font-title text-[22px] leading-tight font-bold">
                    {name}
                </strong>
                <small className="text-[13px] text-muted-foreground">
                    Level {level} · {title}
                </small>
                <XpBar
                    xp={xp}
                    levelFloor={levelFloor}
                    nextLevelXp={nextLevelXp}
                    className="mt-2"
                />
            </div>

            <div className="grid gap-1.5">
                <Link
                    href={edit()}
                    className={actionClasses}
                    title="Settings"
                    aria-label="Settings"
                >
                    <Settings />
                </Link>
                <Link
                    href={logout()}
                    as="button"
                    onClick={() => router.flushAll()}
                    className={actionClasses}
                    title="Log out"
                    aria-label="Log out"
                    data-test="logout-button"
                >
                    <LogOut />
                </Link>
            </div>
        </div>
    );
}
