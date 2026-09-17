import { Head } from '@inertiajs/react';
import { ScrollText } from 'lucide-react';
import { type ReactNode, useCallback, useState } from 'react';

import ChangeLogDetail from '@/components/change-logs/change-log-detail';
import ChangeLogPagination from '@/components/change-logs/change-log-pagination';
import ChangeLogRow from '@/components/change-logs/change-log-row';
import type {
    ChangeLogAnswer,
    ChangeLogEntry,
    ChangeLogPage,
    ChangeLogRow as Row,
} from '@/components/change-logs/types';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { ItemGroup } from '@/components/ui/item';
import { Skeleton } from '@/components/ui/skeleton';
import ChangeLogsLayout from '@/layouts/change-logs-layout';

type Props = {
    items: ChangeLogPage;
    entry: ChangeLogEntry | null;
    previous: Row | null;
    next: Row | null;
    /** The path the routes are registered under, e.g. "/change-logs". */
    baseUrl: string;
};

/**
 * Point the address bar at the entry being read without making a visit.
 *
 * The existing history state is carried forward rather than replaced: Inertia
 * restores a page from `history.state.page` when the reader goes back, and a
 * state object written over with nothing would send it to the server for a
 * page it already had. `replaceState` rather than `pushState` on purpose —
 * reading down a journal is not twelve steps of history to walk back through,
 * and the back button should leave the screen the way it arrived.
 */
function rememberUrl(url: string): void {
    window.history.replaceState(window.history.state, '', url);
}

/**
 * The journal of what was fixed and what was improved.
 *
 * One screen: the list on the left, the entry on the right. Opening an entry
 * is a fetch rather than a visit, so the column keeps its scroll and the
 * reader walking down a page of entries never watches the left half redraw.
 * The url follows anyway, which is what makes an entry something you can
 * paste into a chat.
 *
 * The server hands the first entry of the page already rendered, so the pane
 * is never empty on arrival and the first read costs no round trip.
 */
export default function ChangeLogsIndex({
    items,
    entry,
    previous,
    next,
    baseUrl,
}: Props) {
    const [current, setCurrent] = useState<ChangeLogAnswer | null>(
        entry === null ? null : { entry, previous, next },
    );
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);

    const urlOf = useCallback(
        (slug: string) => `${baseUrl}/${slug}`,
        [baseUrl],
    );

    /*
     * Nothing re-seeds this state when the props change, and nothing has to:
     * paginating the list is an ordinary visit, so the component is remounted
     * and the initialisers above run again on the new page's first entry.
     * Opening an entry, which must not lose the column's scroll, is a fetch
     * and no visit at all.
     */
    const open = useCallback(
        (slug: string) => {
            setFailed(false);
            setLoading(true);

            /*
             * `Accept: application/json` is what makes `expectsJson()` true on
             * the same route an Inertia visit and a cold browser both get the
             * whole screen from — one url, two answers, neither able to drift
             * from the other.
             */
            fetch(urlOf(slug), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(String(response.status));
                    }

                    return response.json() as Promise<ChangeLogAnswer>;
                })
                .then((answer) => {
                    setCurrent(answer);
                    rememberUrl(urlOf(slug));
                })
                .catch(() => setFailed(true))
                .finally(() => setLoading(false));
        },
        [urlOf],
    );

    return (
        <>
            <Head title="Change logs" />

            <div className="grid flex-1 grid-cols-1 overflow-hidden lg:grid-cols-[24rem_minmax(0,1fr)]">
                <div className="flex flex-col overflow-hidden border-b lg:h-full lg:border-r lg:border-b-0">
                    <div className="shrink-0 bg-linear-to-b from-muted/50 to-background/0 p-4 backdrop-blur-md">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Change logs
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            What was fixed, and what got better.
                        </p>
                    </div>

                    {items.data.length === 0 ? (
                        <Empty>
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <ScrollText />
                                </EmptyMedia>
                                <EmptyTitle>Nothing published yet</EmptyTitle>
                                <EmptyDescription>
                                    An entry appears here as soon as a fix or an
                                    improvement is written up.
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    ) : (
                        <>
                            <ItemGroup
                                role="list"
                                data-test="change-logs"
                                className="min-h-0 flex-1 gap-0! overflow-y-auto border-t"
                            >
                                {items.data.map((row) => (
                                    <ChangeLogRow
                                        key={row.slug}
                                        row={row}
                                        href={urlOf(row.slug)}
                                        active={row.slug === current?.entry.slug}
                                        onOpen={open}
                                    />
                                ))}
                            </ItemGroup>

                            {/*
                                At the foot of the column, outside the rows that
                                scroll above it: a control that moves the whole
                                list is never something you scroll to reach.
                            */}
                            <div className="shrink-0 border-t bg-background/80 backdrop-blur-md">
                                <ChangeLogPagination page={items} />
                            </div>
                        </>
                    )}
                </div>

                {/*
                    `min-h-0` and not only `min-w-0`: a grid item's automatic
                    minimum size is its content in both axes, so this pane would
                    grow to the height of the longest entry and push the ceiling
                    open — the pane clips nothing itself, it is the column
                    inside it that scrolls.
                */}
                <section
                    data-test="change-log"
                    className="flex min-h-0 min-w-0 flex-col"
                >
                    {loading ? (
                        <ChangeLogLoading />
                    ) : failed ? (
                        <Empty className="flex-1">
                            <EmptyHeader>
                                <EmptyTitle>
                                    This entry could not be read
                                </EmptyTitle>
                                <EmptyDescription>
                                    Try again in a moment.
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    ) : current === null ? (
                        <Empty className="flex-1">
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <ScrollText />
                                </EmptyMedia>
                                <EmptyTitle>Pick an entry</EmptyTitle>
                            </EmptyHeader>
                        </Empty>
                    ) : (
                        <ChangeLogDetail answer={current} onOpen={open} />
                    )}
                </section>
            </div>
        </>
    );
}

/** What the pane shows while an entry is on its way. */
function ChangeLogLoading() {
    return (
        <div className="flex-1 space-y-4 p-4" aria-busy>
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-1">
                <Skeleton className="h-5 w-32" />
                <Skeleton className="h-8 w-3/4" />
                <div className="space-y-2 pt-4">
                    <Skeleton className="h-4 w-full" />
                    <Skeleton className="h-4 w-full" />
                    <Skeleton className="h-4 w-2/3" />
                </div>
            </div>
        </div>
    );
}

/*
 * The layout below is the neutral one the package installs. Replace it with
 * your application's own — the screen needs a wrapper that caps its height,
 * because every `overflow-y-auto` inside it hangs from that ceiling.
 */
ChangeLogsIndex.layout = (page: ReactNode) => (
    <ChangeLogsLayout>{page}</ChangeLogsLayout>
);
