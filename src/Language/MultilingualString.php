<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Language;

use InvalidArgumentException;

final class MultilingualString
{
    private Language $originalLanguage;

    private string $originalString;

    private array $translations;

    public function __construct(Language $originalLanguage, string $originalString)
    {
        $this->originalLanguage = $originalLanguage;
        $this->originalString = $originalString;
        $this->translations = [];
    }

    public function getOriginalLanguage(): Language
    {
        return $this->originalLanguage;
    }

    public function getOriginalString(): string
    {
        return $this->originalString;
    }

    public function withTranslation(Language $language, string $translation): MultilingualString
    {
        if ($language->getCode() === $this->originalLanguage->getCode()) {
            throw new InvalidArgumentException('Can not translate to original language.');
        }

        $c = clone $this;
        $c->translations[$language->getCode()] = $translation;
        return $c;
    }

    /**
     * @return string[]
     *   Associative array with languages as keys and translations as values.
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /**
     * @return string[]
     *   Associative array with languages as keys and translations as values.
     */
    public function getTranslationsIncludingOriginal(): array
    {
        return array_merge(
            [$this->originalLanguage->getCode() => $this->originalString],
            $this->translations
        );
    }

    /**
     * @param Language ...$fallbackLanguages
     *   One or more accept languages.
     */
    public function getStringForLanguage(Language $preferredLanguage, Language ...$fallbackLanguages): ?string
    {
        $languages = $fallbackLanguages;
        array_unshift($languages, $preferredLanguage);

        $translations = $this->getTranslationsIncludingOriginal();

        foreach ($languages as $language) {
            if (isset($translations[$language->getCode()])) {
                return $translations[$language->getCode()];
            }
        }

        return null;
    }

    public function serialize(): array
    {
        $serialized = [];

        foreach ($this->getTranslationsIncludingOriginal() as $language => $translation) {
            $serialized[$language] = $translation;
        }

        return $serialized;
    }

    public static function deserialize(array $data, ?string $originalLanguage = null): MultilingualString
    {
        $languages = array_keys($data);

        if (!$originalLanguage || !isset($data[$originalLanguage])) {
            $originalLanguage = (string) reset($languages);
        }

        $string = new MultilingualString(new Language($originalLanguage), $data[$originalLanguage]);
        foreach ($data as $language => $translation) {
            if ($language === $originalLanguage) {
                continue;
            }

            $string = $string->withTranslation(new Language($language), $translation);
        }

        return $string;
    }
}
