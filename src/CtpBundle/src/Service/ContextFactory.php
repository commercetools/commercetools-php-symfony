<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Core\Model\Common\Context;

class ContextFactory
{
    private $fallbackLanguages;
    private $converter;
    private $defaults;

    public function __construct(
        $fallbackLanguages,
        LocaleConverter $converter,
        $defaults
    ) {
        $this->fallbackLanguages = $fallbackLanguages;
        $this->converter = $converter;
        $this->defaults = $defaults;
    }

    /**
     * @param string $locale
     * @return Context
     */
    public function build(
        $locale = null
    ) {
        $context = Context::of();
        foreach ($this->defaults as $key => $default) {
            $method = 'set' . ucfirst($key);
            $context->$method($default);
        }
        if (is_null($locale)) {
            $locale = $context->getLocale();
        }

        $locale = $this->converter->convert($locale);
        $fallbackLanguages = $this->fallbackLanguages;

        $language = \Locale::getPrimaryLanguage($locale);
        $languages = [$language];

        if (isset($fallbackLanguages[$language])) {
            $languages = array_merge($languages, $fallbackLanguages[$language]);
        }
        $context->setLanguages($languages)->setLocale($locale);

        return $context;
    }
}
