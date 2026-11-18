<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use ReflectionClass;
use RuntimeException;

trait HtmlFixture
{
    /**
     * Reads the expected HTML from data/<test class without Test>/<name>.html, next to the test.
     *
     * Fixtures may be indented and wrapped freely: every whitespace run in the file (indentation
     * and newlines included) is collapsed into the single space the formatters output between
     * elements, and leading/trailing whitespace is trimmed. Only the expected side is normalized,
     * so the formatter output itself is still compared character by character.
     *
     * When editing a fixture:
     * - tags the formatter writes without any whitespace in between (e.g. `<li><time ...>` or
     *   `</span>/<span ...>`) must stay on the same line, or a space is added that the output
     *   does not have;
     * - expected output with leading/trailing whitespace or two consecutive spaces cannot be
     *   expressed here and has to stay an inline string;
     * - plain text expectations (with significant newlines) do not belong in these fixtures.
     */
    private function expectedHtml(string $name): string
    {
        $class = new ReflectionClass($this);
        $path = dirname((string) $class->getFileName())
            . '/data/' . preg_replace('/Test$/', '', $class->getShortName())
            . '/' . $name . '.html';

        if (!is_file($path)) {
            throw new RuntimeException('Missing HTML fixture ' . $path);
        }

        return (string) preg_replace('/\s+/', ' ', trim((string) file_get_contents($path)));
    }
}
