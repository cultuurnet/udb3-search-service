<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use ReflectionClass;
use RuntimeException;

trait PlainTextFixture
{
    /**
     * Reads the expected plain text from data/<test class without Test>/<name>.txt, next to the
     * test. One line in the file is one line of output: nothing is collapsed, since every space is
     * part of what the formatters write, and \n is read back as PHP_EOL. Newlines at the end of the
     * file are not part of the expectation, so a formatter that terminates its output with a line
     * break says so in the test: $this->expectedText('…') . PHP_EOL.
     */
    private function expectedText(string $name): string
    {
        $class = new ReflectionClass($this);
        $path = dirname((string) $class->getFileName())
            . '/data/' . preg_replace('/Test$/', '', $class->getShortName())
            . '/' . $name . '.txt';

        if (!is_file($path)) {
            throw new RuntimeException('Missing plain text fixture ' . $path);
        }

        return str_replace("\n", PHP_EOL, rtrim((string) file_get_contents($path), "\n"));
    }
}
