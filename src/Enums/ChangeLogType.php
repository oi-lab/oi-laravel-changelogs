<?php

namespace OiLab\OiLaravelChangelogs\Enums;

/**
 * What an entry of the journal announces: something repaired, or something
 * that got better.
 *
 * Two cases and no more. A journal read to answer "what changed since last
 * week" is read by the badge on the row, and a scale of five kinds is a scale
 * nobody classifies twice the same way. Anything that is neither a repair nor
 * an improvement is not worth an entry.
 */
enum ChangeLogType: string
{
    case Fix = 'fix';

    case Improvement = 'improvement';

    /**
     * The label the badge draws, translated by the host application.
     */
    public function label(): string
    {
        return match ($this) {
            self::Fix => __('Fix'),
            self::Improvement => __('Improvement'),
        };
    }

    /**
     * Every value, as the commands print them when one is mistyped.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
