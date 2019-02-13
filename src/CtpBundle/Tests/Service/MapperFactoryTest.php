<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Service;

use Commercetools\Core\Model\JsonObjectMapper;
use Commercetools\Symfony\CtpBundle\Service\ContextFactory;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use PHPUnit\Framework\TestCase;

class MapperFactoryTest extends TestCase
{
    public function testBuild()
    {
        $factory = $this->prophesize(ContextFactory::class);
        $factory->build('foo')->shouldBeCalledOnce();

        $mapperFactory = new MapperFactory($factory->reveal());
        $mapper = $mapperFactory->build('foo');

        $this->assertInstanceOf(JsonObjectMapper::class, $mapper);
    }
}
