import { Link } from '@inertiajs/react';

import type { ChangeLogRow as Row } from '@/components/change-logs/types';
import { Badge } from '@/components/ui/badge';
import { Item, ItemContent, ItemDescription, ItemTitle } from '@/components/ui/item';
import { formatChangeLogDate } from '@/lib/change-log-date';
import { cn } from '@/lib/utils';

/**
 * One entry of the journal, as the column draws it.
 */
export default function ChangeLogRow({
    row,
    href,
    active,
    onOpen,
}: {
    row: Row;
    href: string;
    active: boolean;
    onOpen: (slug: string) => void;
}) {
    return (
        <Item
            size="sm"
            data-active={active ? '' : undefined}
            className={cn(
                'cursor-pointer items-start rounded-none border-b border-l-2 border-l-transparent',
                /*
                 * `Item` already tints the row under the cursor, and it tints
                 * it `bg-muted` — so a selected row drawn in the same token is
                 * indistinguishable from a hovered one, and reading down the
                 * list lights up wherever the mouse happens to rest. The
                 * selection gets a colour of its own and a bar down its left
                 * edge, and keeps both under the cursor.
                 */
                active &&
                    'border-l-primary bg-accent text-accent-foreground hover:bg-accent!',
            )}
            asChild
        >
            {/*
                A Link and not a button, even though the click is answered by a
                fetch rather than by a visit: this is a row that opens a
                document, so it has to be openable in a new tab and readable by
                anything that follows links. `preventDefault` keeps the visit
                from happening on a plain click, and lets a modified one
                through — ctrl-click, middle click, the context menu.
            */}
            <Link
                href={href}
                aria-current={active ? 'page' : undefined}
                data-test="change-log-row"
                onClick={(event) => {
                    if (
                        event.metaKey ||
                        event.ctrlKey ||
                        event.shiftKey ||
                        event.altKey ||
                        event.button !== 0
                    ) {
                        return;
                    }

                    event.preventDefault();
                    onOpen(row.slug);
                }}
            >
                <ItemContent>
                    <ItemTitle className="line-clamp-2 whitespace-normal">
                        {row.title}
                    </ItemTitle>
                    <ItemDescription className="flex items-center gap-2">
                        <Badge
                            variant={row.type === 'fix' ? 'default' : 'secondary'}
                        >
                            {row.typeLabel}
                        </Badge>
                        <span>{formatChangeLogDate(row.date)}</span>
                    </ItemDescription>
                </ItemContent>
            </Link>
        </Item>
    );
}
