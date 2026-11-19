<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class TranslatorTest extends TestCase
{
    public function testEveryLanguageHasTheSameLabels(): void
    {
        $property = new ReflectionProperty(Translator::class, 'messages');
        $property->setAccessible(true);
        $messages = $property->getValue(new Translator('nl_BE'));

        $this->assertSame(['en', 'nl', 'fr', 'de'], array_keys($messages));

        $expectedKeys = array_keys($messages['nl']);
        sort($expectedKeys);
        foreach ($messages as $language => $labels) {
            $keys = array_keys($labels);
            sort($keys);
            $this->assertSame($expectedKeys, $keys, $language . ' has different labels than nl');
        }
    }

    public function testItRejectsAnUnsupportedLanguage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported locale: es_ES');

        new Translator('es_ES');
    }
}
