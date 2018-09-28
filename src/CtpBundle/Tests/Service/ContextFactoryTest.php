<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Service;


use Commercetools\Core\Model\Common\Context;
use Commercetools\Symfony\CtpBundle\Service\ContextFactory;
use Commercetools\Symfony\CtpBundle\Service\LocaleConverter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ContextFactoryTest extends TestCase
{
    public function testBuild()
    {
        $fallbackLanguages = ['foo'];
        $converter = $this->prophesize(LocaleConverter::class);
        $converter->convert(Argument::type('string'))->willReturn('foo')->shouldBeCalledOnce();
        $defaults = [
            'graceful' => true
        ];

        $contextFactory = new ContextFactory($fallbackLanguages, $converter->reveal(), $defaults);
        $context = $contextFactory->build();

        $this->assertInstanceOf(Context::class, $context);
        $this->assertCount(1, $context->getLanguages());
        $this->assertSame('foo', current($context->getLanguages()));
        $this->assertSame('foo', $context->getLocale());
    }

}
