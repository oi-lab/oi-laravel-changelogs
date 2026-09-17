<?php

namespace OiLab\OiLaravelChangelogs\Data;

use OiLab\OiLaravelChangelogs\Enums\ChangeLogType;
use OiLab\OiLaravelChangelogs\Services\ChangeLogEntry;
use Spatie\LaravelData\Data;

/**
 * One row of the list: everything a reader needs to choose an entry, and
 * nothing it takes a markdown converter to produce.
 */
class ChangeLogRowData extends Data
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
    ) {}

    public static function fromEntry(ChangeLogEntry $entry): self
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
        );
    }
}
