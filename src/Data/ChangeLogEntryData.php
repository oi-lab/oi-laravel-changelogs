<?php

namespace OiLab\OiLaravelChangelogs\Data;

use OiLab\OiLaravelChangelogs\Enums\ChangeLogType;
use OiLab\OiLaravelChangelogs\Services\ChangeLogEntry;
use OiLab\OiLaravelChangelogs\Services\ChangeLogMarkdown;
use Spatie\LaravelData\Data;

/**
 * The entry being read, with its content in the shape the configured
 * rendering engine asked for.
 *
 * Exactly one of "html" and "markdown" is ever filled. Sending both would
 * double the payload of the longest thing on the screen for a component that
 * can only draw one of them, and leaving the choice to the browser would put
 * the decision in two places.
 *
 * A flat class rather than an extension of ChangeLogRowData: the two shapes
 * are generated into TypeScript as they stand, and a reader that gets handed
 * a row where an entry was expected should not type-check.
 */
class ChangeLogEntryData extends Data
{
    /**
     * @param  list<string>  $tags
     * @param  list<string>  $commits
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $date,
        public readonly ChangeLogType $type,
        public readonly string $typeLabel,
        public readonly array $tags,
        public readonly array $commits,
        public readonly string $summary,
        public readonly ?string $html = null,
        public readonly ?string $markdown = null,
    ) {}

    public static function fromEntry(ChangeLogEntry $entry, ChangeLogMarkdown $markdown, bool $onServer = true): self
    {
        return new self(
            slug: $entry->slug,
            title: $entry->title,
            date: $entry->date->toDateString(),
            type: $entry->type,
            typeLabel: $entry->type->label(),
            tags: $entry->tags,
            commits: $entry->commits,
            summary: $entry->summary,
            html: $onServer ? $markdown->toHtml($entry->markdown) : null,
            markdown: $onServer ? null : $entry->markdown,
        );
    }
}
