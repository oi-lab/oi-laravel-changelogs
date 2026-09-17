import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';

import { CHANGE_LOG_TYPOGRAPHY_CLASS } from '@/lib/change-log-typography';
import { cn } from '@/lib/utils';

/**
 * An entry converted in the browser (rendering.markdown_engine = "client").
 *
 * Deliberately thin: an entry is a paragraph, a heading and a bullet list, so
 * there is nothing here to highlight, diagram or copy to the clipboard. If
 * yours grow code blocks worth colouring, add a rehype plugin below rather
 * than reaching for the documentation renderer.
 *
 * Requires: react-markdown, remark-gfm.
 */
export default function ChangeLogMarkdownContent({
    markdown,
    className,
}: {
    markdown: string;
    className?: string;
}) {
    return (
        <div
            data-slot="change-log-markdown"
            className={cn(CHANGE_LOG_TYPOGRAPHY_CLASS, className)}
        >
            <ReactMarkdown remarkPlugins={[remarkGfm]}>
                {markdown}
            </ReactMarkdown>
        </div>
    );
}
