<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Model\Repository;


use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\JsonDeserializeInterface;
use Commercetools\Core\Request\AbstractApiRequest;
use Commercetools\Core\Response\ApiResponseInterface;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CartBundle\Model\Repository\ShippingMethodRepository;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Cache\CacheItemPoolInterface;

class CartRepositoryTest extends TestCase
{
    private $cache;
    private $client;
    private $mapperFactory;
    private $shippingMethodRepository;

    protected function setUp()
    {
        $this->cache = $this->prophesize(CacheItemPoolInterface::class);
        $this->client = $this->prophesize(Client::class);
        $this->mapperFactory = $this->prophesize(MapperFactory::class);
        $this->shippingMethodRepository = $this->prophesize(ShippingMethodRepository::class);
    }

    public function testExecuteRequest()
    {
        $cartRepository = new CartRepository(
            0,
            $this->cache->reveal(),
            $this->client->reveal(),
            $this->mapperFactory->reveal(),
            $this->shippingMethodRepository->reveal(),
        );

        $request = $this->prophesize(AbstractApiRequest::class);
        $response = $this->prophesize(ApiResponseInterface::class);
        $cartResponse = $this->prophesize(JsonDeserializeInterface::class);
        $request->executeWithClient(Argument::type(Client::class))
            ->willReturn($response->reveal())
            ->shouldBeCalled();
        $request->mapFromResponse(
            Argument::type(ApiResponseInterface::class),
            null)
            ->willReturn($cartResponse->reveal())
            ->shouldBeCalled();
        $carts = $cartRepository->executeRequest($request->reveal(), 'en');
        $this->assertInstanceOf(JsonDeserializeInterface::class, $carts);
    }

    public function testCreateCart()
    {

    }
}
