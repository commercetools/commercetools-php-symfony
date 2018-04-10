<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Service;

class LocaleConverter
{
    private $locales;
    private $country;

    public function __construct($country)
    {
        $this->locales = [];
        $this->country = $country;
    }

    /**
     * @param $locale
     * @return string
     */
    public function convert($locale)
    {
        if (!isset($this->locales[$locale])) {
            $parts = \Locale::parseLocale($locale);
            if (!isset($parts['region'])) {
                $parts['region'] = $this->country;
            }
            $this->locales[$locale] = \Locale::canonicalize(\Locale::composeLocale($parts));
        }

        return $this->locales[$locale];
    }
}
