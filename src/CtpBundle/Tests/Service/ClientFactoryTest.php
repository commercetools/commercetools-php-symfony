<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Service;

use Commercetools\Core\Client\HttpClient;
use Commercetools\Core\Config;
use Commercetools\Symfony\CtpBundle\Profiler\CommercetoolsProfilerExtension;
use Commercetools\Symfony\CtpBundle\Profiler\ProfileMiddleware;
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
    private $converter;
    private $config;
    private $profiler;

    public function setUp()
    {
        $this->config = $this->prophesize(Config::class);
        $this->contextFactory = $this->prophesize(ContextFactory::class);
        $this->converter = $this->prophesize(LocaleConverter::class);
        $this->profiler = $this->prophesize(CommercetoolsProfilerExtension::class);

        $this->cache = new ExternalAdapter();
    }

    public function testBuild()
    {
        $factory = new ClientFactory(
            Config::fromArray([
                'client_id' => 'foo',
                'client_secret' => 'bar',
                'project' => 'baz',
            ]),
            $this->contextFactory->reveal(),
            $this->cache,
            $this->converter->reveal()
        );

        $client = $factory->build();

        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testBuildWithProfilerExtension()
    {
        $logger = $this->prophesize(LoggerInterface::class);

        $profiler = $this->prophesize(CommercetoolsProfilerExtension::class);
//        $profiler->getProfileMiddleWare()->willReturn(ProfileMiddleware::create($profiler->reveal()))->shouldBeCalledOnce();

        $factory = new ClientFactory(
            $this->config->reveal(),
            $this->contextFactory->reveal(),
            $this->cache,
            $this->converter->reveal(),
            $logger->reveal(),
            $profiler->reveal()
        );

        $client = $factory->build('en', null, [
            'client_id' => 'foo',
            'client_secret' => 'bar',
            'project' => 'baz',
        ]);

        $this->assertInstanceOf(HttpClient::class, $client);
    }
}
