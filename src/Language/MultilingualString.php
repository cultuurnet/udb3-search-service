<?php
declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Language;

use CultuurNet\UDB3\Language;
use ValueObjects\StringLiteral\StringLiteral;

final class MultilingualString
{
    /**
     * @var Language
     */
    private $originalLanguage;

    /**
     * @var StringLiteral
     */
    private $originalString;

    /**
     * @var StringLiteral[]
     *   Associative array with languages as keys and translations as values.
     */
    private $translations;

    public function __construct(Language $originalLanguage, StringLiteral $originalString)
    {
        $this->originalLanguage = $originalLanguage;
        $this->originalString = $originalString;
        $this->translations = [];
    }

    public function getOriginalLanguage(): Language
    {
        return $this->originalLanguage;
    }

    public function getOriginalString(): StringLiteral
    {
        return $this->originalString;
    }

    public function withTranslation(Language $language, StringLiteral $translation): MultilingualString
    {
        if ($language->getCode() == $this->originalLanguage->getCode()) {
            throw new \InvalidArgumentException('Can not translate to original language.');
        }

        $c = clone $this;
        $c->translations[$language->getCode()] = $translation;
        return $c;
    }

    /**
     * @return StringLiteral[]
     *   Associative array with languages as keys and translations as values.
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /**
     * @return StringLiteral[]
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
     * @param Language $preferredLanguage
     * @param Language[] ...$fallbackLanguages
     *   One or more accept languages.
     * @return StringLiteral|null
     */
    public function getStringForLanguage(Language $preferredLanguage, Language ...$fallbackLanguages): ?StringLiteral
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
            $serialized[$language] = $translation->toNative();
        }

        return $serialized;
    }

    public static function deserialize(array $data, ?string $originalLanguage = null): MultilingualString
    {
        $languages = array_keys($data);

        if (!$originalLanguage || !isset($data[$originalLanguage])) {
            $originalLanguage = reset($languages);
        }

        $string = new MultilingualString(new Language($originalLanguage), new StringLiteral($data[$originalLanguage]));
        foreach ($data as $language => $translation) {
            if ($language === $originalLanguage) {
                continue;
            }

            $string = $string->withTranslation(new Language($language), new StringLiteral($translation));
        }

        return $string;
    }
}
