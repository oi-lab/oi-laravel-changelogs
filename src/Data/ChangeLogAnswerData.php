<?php

namespace OiLab\OiLaravelChangelogs\Data;

use Spatie\LaravelData\Data;

/**
 * What the show route answers a fetch with: the entry, and the two rows
 * either side of it.
 *
 * "Previous" is the row above in the list — the newer entry — so the two
 * buttons walk the list the reader is looking at rather than the calendar.
 */
class ChangeLogAnswerData extends Data
{
    public function __construct(
        public readonly ChangeLogEntryData $entry,
        public readonly ?ChangeLogRowData $previous = null,
        public readonly ?ChangeLogRowData $next = null,
    ) {}
}
