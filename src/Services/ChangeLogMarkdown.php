<?php

namespace OiLab\OiLaravelChangelogs\Services;

use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * The markdown of an entry, turned into the html the screen draws.
 *
 * Its own class rather than a method of the repository because the converter
 * is expensive to build and worth keeping: under Octane the container
 * survives the request, and a singleton binding hands the same instance to
 * every read of the same worker. Nothing request-specific is held here, which
 * is what makes that safe.
 *
 * Raw html is escaped. A change log is prose and a bullet list, written in the
 * repository by whoever fixed the thing — there is no markup to let through,
 * and the narrower setting is the one to keep by default.
 */
class ChangeLogMarkdown
{
    private ?GithubFlavoredMarkdownConverter $converter = null;

    public function toHtml(string $markdown): string
    {
        $this->converter ??= new GithubFlavoredMarkdownConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        return (string) $this->converter->convert($markdown);
    }
}
