<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Monolog\LogRecord;

class ColoredLineFormatter extends LineFormatter
{
    public function __construct(bool $colorsEnabled = true)
    {
        parent::__construct(null, null, true, true);

        $this->colorsEnabled = $colorsEnabled;
    }

    public function format(LogRecord $record): string
    {
        $line = parent::format($record);

        if (! $this->colorsEnabled) {
            return $line;
        }

        $color = match (true) {
            $record->level->value >= Level::Critical->value => "\033[31m",
            $record->level->value >= Level::Warning->value => "\033[33m",
            default => "\033[32m",
        };

        return $color.$line."\033[0m";
    }

    private bool $colorsEnabled;
}
