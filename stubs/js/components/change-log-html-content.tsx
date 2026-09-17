import { router } from '@inertiajs/react';
import { type MouseEvent } from 'react';

import { CHANGE_LOG_TYPOGRAPHY_CLASS } from '@/lib/change-log-typography';
import { cn } from '@/lib/utils';

/**
 * An entry whose markdown was converted to html on the server
 * (rendering.markdown_engine = "server").
 *
 * The html is built in Laravel because the journal is written by the people
 * who write the application and never by a reader: what goes through the
 * converter is a file of the repository, and that is one markdown renderer
 * fewer to ship to the browser.
 */
export default function ChangeLogHtmlContent({
    html,
    className,
}: {
    html: string;
    className?: string;
}) {
    /*
     * Links inside the entry are handed to Inertia rather than to the browser,
     * so an entry pointing at another screen of the application does not throw
     * away the page. Anything absolute, anchored or targeted is left alone.
     */
    const handleClick = (event: MouseEvent<HTMLDivElement>) => {
        const anchor = (event.target as HTMLElement).closest('a');

        if (!anchor) {
            return;
        }

        const href = anchor.getAttribute('href');

        if (
            !href ||
            href.startsWith('#') ||
            anchor.target === '_blank' ||
            /^[a-z]+:/i.test(href)
        ) {
            return;
        }

        event.preventDefault();
        router.visit(href);
    };

    return (
        <div
            data-slot="change-log-html"
            className={cn(CHANGE_LOG_TYPOGRAPHY_CLASS, className)}
            onClick={handleClick}
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}
