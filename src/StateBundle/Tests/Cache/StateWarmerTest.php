<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Cache;

use Commercetools\Symfony\StateBundle\Cache\StateKeyResolver;
use Commercetools\Symfony\StateBundle\Cache\StateWarmer;
use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class StateWarmerTest extends TestCase
{
    public function testWarmUp()
    {
        $stateKeyResolver = $this->prophesize(StateKeyResolver::class);
        $logger = $this->prophesize(LoggerInterface::class);
        $stateKeyResolver->fillCache()->shouldBeCalledOnce();

        $stateWarmer = new StateWarmer($stateKeyResolver->reveal(), $logger->reveal());
        $this->assertTrue($stateWarmer->isOptional());
        $stateWarmer->warmUp(null);
    }

    public function testWarmUpWithError()
    {
        $stateKeyResolver = $this->prophesize(StateKeyResolver::class);
        /** @var LoggerInterface $logger */
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->info(Argument::is('Could not fetch states from commercetools'))->shouldBeCalledOnce();

        $request = $this->prophesize(RequestInterface::class);
        $response = $this->prophesize(ResponseInterface::class);

        $stateKeyResolver->fillCache()->will(function () use ($request, $response) {
            throw new ClientException('error', $request->reveal(), $response->reveal());
        })->shouldBeCalledOnce();

        $stateWarmer = new StateWarmer($stateKeyResolver->reveal(), $logger->reveal());
        $stateWarmer->warmUp('');
    }
}
