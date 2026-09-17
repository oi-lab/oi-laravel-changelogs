<?php

namespace OiLab\OiLaravelChangelogs\Services;

use Carbon\CarbonImmutable;
use OiLab\OiLaravelChangelogs\Data\ChangeLogEntryData;
use OiLab\OiLaravelChangelogs\Data\ChangeLogRowData;
use OiLab\OiLaravelChangelogs\Enums\ChangeLogType;

/**
 * One entry of the journal: one file of the change log directory, parsed.
 *
 * Two shapes leave this object rather than one. The list draws a row from the
 * frontmatter and the opening paragraph alone, and there may be a hundred of
 * them on a page; the reader opening an entry gets the whole thing. Sending
 * the second shape for every row would put the entire journal on the wire to
 * draw a column 24rem wide.
 */
readonly class ChangeLogEntry
{
    /**
     * @param  list<string>  $tags
     * @param  list<string>  $commits
     */
    public function __construct(
        public string $slug,
        public string $title,
        public CarbonImmutable $date,
        public ChangeLogType $type,
        public array $tags,
        public array $commits,
        public string $summary,
        public string $markdown,
    ) {}

    /**
     * The row the list draws: what a reader needs to choose an entry.
     */
    public function toRow(): ChangeLogRowData
    {
        return ChangeLogRowData::fromEntry($this);
    }

    /**
     * The entry itself, carrying whichever of the two contents the configured
     * rendering engine asked for.
     */
    public function toDetail(ChangeLogMarkdown $markdown, bool $onServer = true): ChangeLogEntryData
    {
        return ChangeLogEntryData::fromEntry($this, $markdown, $onServer);
    }
}
