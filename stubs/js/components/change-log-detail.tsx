import { ChevronLeft, ChevronRight, GitCommitHorizontal } from 'lucide-react';
import { Fragment } from 'react';

import ChangeLogHtmlContent from '@/components/change-logs/change-log-html-content';
import ChangeLogMarkdownContent from '@/components/change-logs/change-log-markdown-content';
import type { ChangeLogAnswer } from '@/components/change-logs/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatChangeLogDate } from '@/lib/change-log-date';

/**
 * The entry being read, with the two buttons that walk the list.
 */
export default function ChangeLogDetail({
    answer,
    onOpen,
}: {
    answer: ChangeLogAnswer;
    onOpen: (slug: string) => void;
}) {
    const { entry, previous, next } = answer;

    return (
        <div className="flex flex-1 flex-col overflow-hidden">
            <div className="shrink-0 bg-linear-to-b from-muted/50 to-background/0 p-4 backdrop-blur-md">
                <div className="mx-auto flex w-full max-w-3xl flex-col gap-2">
                    <div className="flex items-center justify-between gap-2">
                        <Badge
                            variant={
                                entry.type === 'fix' ? 'default' : 'secondary'
                            }
                        >
                            {entry.typeLabel}
                        </Badge>
                        <span className="text-sm text-muted-foreground">
                            {formatChangeLogDate(entry.date)}
                        </span>
                    </div>
                    <h2 className="text-2xl font-semibold tracking-tight">
                        {entry.title}
                    </h2>
                </div>
            </div>

            <div className="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-4">
                {entry.commits.length > 0 && (
                    <div className="mx-auto flex w-full max-w-3xl flex-wrap items-center gap-y-0.5 font-mono text-xs text-muted-foreground">
                        <GitCommitHorizontal className="mr-2 size-3.5" />
                        {entry.commits.map((commit, index) => (
                            <Fragment key={commit}>
                                {index > 0 && <span className="px-0.5">/</span>}
                                <span>{commit}</span>
                            </Fragment>
                        ))}
                    </div>
                )}

                <div
                    data-test="change-log-content"
                    className="mx-auto w-full max-w-3xl"
                >
                    {entry.html !== null ? (
                        <ChangeLogHtmlContent html={entry.html} />
                    ) : (
                        <ChangeLogMarkdownContent markdown={entry.markdown ?? ''} />
                    )}
                </div>

                <div className="mx-auto flex w-full max-w-3xl flex-wrap gap-1 py-8">
                    {entry.tags.map((tag) => (
                        <Badge key={tag} variant="secondary" className="font-normal">
                            {tag}
                        </Badge>
                    ))}
                </div>
            </div>

            {/*
                Previous is the row above — the newer entry — so the two
                buttons walk the list the reader is looking at rather than the
                calendar.
            */}
            <div className="flex items-center justify-between gap-2 border-t bg-background/80 p-4 backdrop-blur-md">
                <Button
                    type="button"
                    variant="link"
                    disabled={previous === null}
                    data-test="change-log-previous"
                    onClick={() => previous && onOpen(previous.slug)}
                >
                    <ChevronLeft />
                    <span className="max-w-72 truncate lg:max-w-fit">
                        {previous?.title ?? 'Previous'}
                    </span>
                </Button>

                <Button
                    type="button"
                    variant="link"
                    disabled={next === null}
                    data-test="change-log-next"
                    onClick={() => next && onOpen(next.slug)}
                >
                    <span className="max-w-72 truncate lg:max-w-fit">
                        {next?.title ?? 'Next'}
                    </span>
                    <ChevronRight />
                </Button>
            </div>
        </div>
    );
}
