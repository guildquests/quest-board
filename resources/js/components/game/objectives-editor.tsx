import { Plus, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { fieldInputClassName } from '@/lib/form-styles';

type ObjectivesEditorProps = {
    /** Starting values; an empty list starts with two blank rows. */
    defaultValues?: string[];
    /** Server errors by field: `objectives` for the list, `objectives.N` for a row. */
    errors?: Record<string, string | undefined>;
    max?: number;
    className?: string;
};

type Row = { id: number; value: string };

function initialRows(defaultValues?: string[]): Row[] {
    const values =
        defaultValues && defaultValues.length > 0 ? defaultValues : ['', ''];

    return values.map((value, id) => ({ id, value }));
}

/**
 * A growing list of text inputs named `objectives[]`, so it submits with the
 * form. At least one row always stays. The parent reads nothing back. Row
 * values are tracked here only to line up server errors with the right row.
 */
export function ObjectivesEditor({
    defaultValues,
    errors = {},
    max = 8,
    className,
}: ObjectivesEditorProps): React.JSX.Element {
    const [rows, setRows] = useState<Row[]>(() => initialRows(defaultValues));
    // Ids only need to be unique; the first rows use their index.
    const nextId = useRef(rows.length);
    const inputs = useRef(new Map<number, HTMLInputElement>());
    const focusId = useRef<number | null>(null);

    // Focus the row that "Add objective" just created.
    useEffect(() => {
        if (focusId.current !== null) {
            inputs.current.get(focusId.current)?.focus();
            focusId.current = null;
        }
    }, [rows]);

    const add = (): void => {
        const row: Row = { id: nextId.current++, value: '' };
        focusId.current = row.id;
        setRows((current) => [...current, row]);
    };

    const remove = (id: number): void => {
        setRows((current) =>
            current.length > 1
                ? current.filter((row) => row.id !== id)
                : current,
        );
    };

    const change = (id: number, value: string): void => {
        setRows((current) =>
            current.map((row) => (row.id === id ? { ...row, value } : row)),
        );
    };

    const listError = errors.objectives;

    // The server drops blank rows before validating, so error `objectives.k`
    // belongs to the k-th row that has text. Blank rows get no row error.
    const rowErrors = new Map<number, string | undefined>();
    let kept = 0;

    for (const row of rows) {
        if (row.value.trim() !== '') {
            rowErrors.set(row.id, errors[`objectives.${kept}`]);
            kept++;
        }
    }

    return (
        <div className={className}>
            <ul className="mb-2 grid gap-2">
                {rows.map((row, index) => {
                    const error = rowErrors.get(row.id);

                    return (
                        <li key={row.id} className="grid gap-1">
                            <div className="flex items-center gap-2.5">
                                <span
                                    aria-hidden
                                    className="size-5 flex-none rounded-md border-[1.5px] border-accent/60"
                                />
                                <Input
                                    ref={(element) => {
                                        if (element) {
                                            inputs.current.set(row.id, element);
                                        } else {
                                            inputs.current.delete(row.id);
                                        }
                                    }}
                                    name="objectives[]"
                                    value={row.value}
                                    onChange={(event) =>
                                        change(row.id, event.target.value)
                                    }
                                    maxLength={120}
                                    placeholder={
                                        index === 0
                                            ? 'First step to finish'
                                            : 'Next step'
                                    }
                                    aria-label={`Objective ${index + 1}`}
                                    aria-invalid={error ? true : undefined}
                                    autoComplete="off"
                                    className={fieldInputClassName}
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`Remove objective ${index + 1}`}
                                    disabled={rows.length === 1}
                                    onClick={() => remove(row.id)}
                                >
                                    <X aria-hidden />
                                </Button>
                            </div>
                            {error && (
                                <p className="pl-[30px] text-sm text-red-400">
                                    {error}
                                </p>
                            )}
                        </li>
                    );
                })}
            </ul>
            {listError && (
                <p className="mb-2 text-sm text-red-400">{listError}</p>
            )}
            <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={rows.length >= max}
                onClick={add}
            >
                <Plus aria-hidden />
                Add objective
            </Button>
        </div>
    );
}
