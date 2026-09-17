import { type ReactNode } from 'react';

/**
 * The ceiling every `overflow-y-auto` of the journal hangs from.
 *
 * An application shell is usually `min-h-svh`, a floor: a page taller than the
 * window simply makes the document scroll, and a pane asked to scroll inside
 * it never does — it has no height to scroll within, so it grows and takes the
 * document with it. This caps the wrapper at `h-svh` and carries `min-h-0`
 * down to the page, which is what lets the list and the entry each scroll on
 * their own.
 *
 * Replace it with your application's layout, keeping those two constraints.
 */
export default function ChangeLogsLayout({
    children,
}: {
    children: ReactNode;
}) {
    return (
        <div className="flex h-svh min-h-0 flex-col bg-background text-foreground">
            {children}
        </div>
    );
}
