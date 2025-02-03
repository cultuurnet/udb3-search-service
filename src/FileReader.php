<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

final class FileReader
{
    public static function read(string $filepath): string
    {
        try {
            $content = file_get_contents($filepath);
        }
        catch (\Exception $e) {
            $content = false;
        }

        if ($content === false) {
            throw new \RuntimeException('Failed to read file ' . $filepath);
        }

        return $content;
    }
}