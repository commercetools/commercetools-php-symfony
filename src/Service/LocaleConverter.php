<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Service;

class LocaleConverter
{
    private $country;

    public function __construct($country)
    {
        $this->country = $country;
    }

    public function convert($locale)
    {
        $parts = \Locale::parseLocale($locale);
        if (!isset($parts['region'])) {
            $parts['region'] = $this->country;
        }
        $locale = \Locale::canonicalize(\Locale::composeLocale($parts));

        return $locale;
    }
}
