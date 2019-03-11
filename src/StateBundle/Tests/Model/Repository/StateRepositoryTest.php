<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Model\Repository;

use Commercetools\Core\Client;
use Commercetools\Core\Request\States\StateByIdGetRequest;
use Commercetools\Core\Request\States\StateQueryRequest;
use Commercetools\Core\Response\ResourceResponse;
use Commercetools\Symfony\CtpBundle\Logger\Logger;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Cache\Tests\Fixtures\ExternalAdapter;

class StateRepositoryTest extends TestCase
{
    private $cache;
    private $mapperFactory;
    private $response;
    private $client;
    private $logger;

    protected function setUp()
    {
        $this->cache = new ExternalAdapter();
        $this->mapperFactory = $this->prophesize(MapperFactory::class);
        $this->logger = $this->prophesize(Logger::class);

        $this->response = $this->prophesize(ResourceResponse::class);
        $this->response->toArray()->willReturn([]);
        $this->response->getContext()->willReturn(null);
        $this->response->isError()->willReturn(false);

        $this->client = $this->prophesize(Client::class);
    }

    private function getStateRepository()
    {
        return new StateRepository(
            false,
            $this->cache,
            $this->client->reveal(),
            $this->mapperFactory->reveal(),
            $this->logger->reveal()
        );
    }

    public function testGetStates()
    {
        $this->client->execute(
            Argument::type(StateQueryRequest::class),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getStateRepository();
        $repository->getStates();
    }

    public function testGetById()
    {
        $this->client->execute(
            Argument::that(function (StateByIdGetRequest $request) {
                static::assertSame('state-1', $request->getId());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getStateRepository();
        $repository->getById('state-1');
    }

    public function testGetByTypeAndKey()
    {
        $this->client->execute(
            Argument::that(function (StateQueryRequest $request) {
                static::assertContains(urlencode('type = "type-1"'), (string)$request->httpRequest()->getUri());
                static::assertContains(urlencode('key = "key-1"'), (string)$request->httpRequest()->getUri());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getStateRepository();
        $repository->getByTypeAndKey('type-1', 'key-1');
    }

    public function testGetByKey()
    {
        $this->client->execute(
            Argument::that(function (StateQueryRequest $request) {
                static::assertNotContains('type', (string)$request->httpRequest()->getUri());
                static::assertContains(urlencode('key = "key-1"'), (string)$request->httpRequest()->getUri());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getStateRepository();
        $repository->getByKey('key-1');
    }
}
