<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Service;

use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Symfony\CtpBundle\Profiler\CommercetoolsProfilerExtension;
use Commercetools\Symfony\CtpBundle\Service\ClientFactory;
use Commercetools\Symfony\CtpBundle\Service\ContextFactory;
use Commercetools\Symfony\CtpBundle\Service\LocaleConverter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Tests\Fixtures\ExternalAdapter;

class ClientFactoryTest extends TestCase
{
    private $contextFactory;
    private $cache;
    private $logger;
    private $converter;
    private $config;
    private $profiler;

    public function setUp()
    {
        $this->config = $this->prophesize(Config::class);
        $this->contextFactory = $this->prophesize(ContextFactory::class);
        $this->converter = $this->prophesize(LocaleConverter::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->profiler = $this->prophesize(CommercetoolsProfilerExtension::class);

        $this->cache = new ExternalAdapter();

    }

    public function testBuild()
    {
        $factory = new ClientFactory(
            $this->config->reveal(), $this->contextFactory->reveal(), $this->cache, $this->converter->reveal(), $this->logger->reveal()
        );

        $client = $factory->build();

        $this->assertInstanceOf(Client::class, $client);
    }
}
