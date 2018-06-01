<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Service;


use Commercetools\Symfony\CtpBundle\Service\LocaleConverter;
use PHPUnit\Framework\TestCase;

class LocaleConverterTest extends TestCase
{
    protected function getConverter($country)
    {
        $converter = new LocaleConverter($country);

        return $converter;
    }

    public function getLocales()
    {
        return [
            ['DE', 'de', 'de_DE'],
            ['DE', 'de_DE', 'de_DE'],
            ['CH', 'de-DE', 'de_DE'],
        ];
    }

    /**
     * @dataProvider getLocales
     */
    public function testConverter($country, $locale, $expectedLocale)
    {
        $this->assertSame($expectedLocale, $this->getConverter($country)->convert($locale));
    }
}
