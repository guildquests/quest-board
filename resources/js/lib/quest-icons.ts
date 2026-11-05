import {
    BookOpen,
    Crown,
    Eye,
    FileText,
    FlaskConical,
    Hourglass,
    Leaf,
    LockOpen,
    Mountain,
    MountainSnow,
    Wrench,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type {
    QuestCategory,
    QuestDifficulty,
    QuestStatus,
} from '@/types/quests';

export const categoryIcons: Record<QuestCategory, LucideIcon> = {
    research: BookOpen,
    tooling: Wrench,
    docs: FileText,
    experiment: FlaskConical,
};

export const statusIcons: Record<QuestStatus, LucideIcon> = {
    open: LockOpen,
    in_progress: Hourglass,
    in_review: Eye,
    done: Crown,
};

export const difficultyIcons: Record<QuestDifficulty, LucideIcon> = {
    easy: Leaf,
    medium: Mountain,
    hard: MountainSnow,
};
