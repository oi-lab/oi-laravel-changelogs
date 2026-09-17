import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { type ReactNode } from 'react';

import type { ChangeLogPage } from '@/components/change-logs/types';
import { Button } from '@/components/ui/button';

/**
 * The band at the foot of the column.
 *
 * Previous, next and a counter — nothing wider fits: a list laid out in a
 * column 24rem across has no more room for a row of numbered pages than a
 * phone does. Paging is an ordinary Inertia visit, which is what remounts the
 * page on the new slice.
 */
export default function ChangeLogPagination({ page }: { page: ChangeLogPage }) {
    if (page.total === 0) {
        return null;
    }

    return (
        <div className="flex items-center gap-1 p-2">
            <PaginationLink
                url={page.prev_page_url}
                label="Previous page"
            >
                <ArrowLeft />
            </PaginationLink>

            <PaginationLink url={page.next_page_url} label="Next page">
                <ArrowRight />
            </PaginationLink>

            <div className="ml-auto pr-2 text-xs font-semibold text-muted-foreground">
                {`${page.current_page}/${page.last_page}`}
            </div>
        </div>
    );
}

function PaginationLink({
    url,
    label,
    children,
}: {
    url: string | null;
    label: string;
    children: ReactNode;
}) {
    if (url === null) {
        return (
            <Button variant="ghost" size="icon" disabled aria-label={label}>
                {children}
            </Button>
        );
    }

    return (
        <Button variant="ghost" size="icon" aria-label={label} asChild>
            <Link href={url} preserveScroll>
                {children}
            </Link>
        </Button>
    );
}
