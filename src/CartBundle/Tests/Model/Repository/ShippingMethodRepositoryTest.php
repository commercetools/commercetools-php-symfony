<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Model\Repository;

use Commercetools\Core\Client\HttpClient;
use Commercetools\Core\Model\ShippingMethod\ShippingMethod;
use Commercetools\Core\Model\ShippingMethod\ShippingMethodCollection;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Core\Request\ShippingMethods\ShippingMethodByCartIdGetRequest;
use Commercetools\Core\Request\ShippingMethods\ShippingMethodByIdGetRequest;
use Commercetools\Core\Request\ShippingMethods\ShippingMethodByLocationGetRequest;
use Commercetools\Core\Response\ResourceResponse;
use Commercetools\Symfony\CartBundle\Model\Repository\ShippingMethodRepository;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Cache\Tests\Fixtures\ExternalAdapter;

class ShippingMethodRepositoryTest extends TestCase
{
    private $cache;
    private $mapperFactory;
    private $response;
    private $client;

    protected function setUp()
    {
        $this->cache = new ExternalAdapter();
        $this->mapperFactory = $this->prophesize(MapperFactory::class);

        $this->response = $this->prophesize(ResourceResponse::class);
        $this->response->toArray()->willReturn([]);
        $this->response->getContext()->willReturn(null);
        $this->response->isError()->willReturn(false);

        $this->client = $this->prophesize(HttpClient::class);
    }

    private function getShippingMethodRepository()
    {
        return new ShippingMethodRepository(
            false,
            $this->cache,
            $this->client->reveal(),
            $this->mapperFactory->reveal()
        );
    }

    public function testGetShippingMethodsByLocation()
    {
        $this->client->execute(
            Argument::that(function (ShippingMethodByLocationGetRequest $request) {
                static::assertSame(ShippingMethodCollection::class, $request->getResultClass());
                static::assertSame(
                    'shipping-methods?country=DE',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $shippingMethodRepo = $this->getShippingMethodRepository();
        $shippingMethodRepo->getShippingMethodsByLocation('en', Location::of()->setCountry('DE'));
    }

    public function testGetShippingMethodsByLocationAndCurrency()
    {
        $this->client->execute(
            Argument::that(function (ShippingMethodByLocationGetRequest $request) {
                static::assertSame(ShippingMethodCollection::class, $request->getResultClass());
                static::assertSame(
                    'shipping-methods?country=DE&currency=EUR',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $shippingMethodRepo = $this->getShippingMethodRepository();
        $shippingMethodRepo->getShippingMethodsByLocation('en', Location::of()->setCountry('DE'), 'EUR');
    }

    public function testGetShippingMethodsByCart()
    {
        $this->client->execute(
            Argument::that(function (ShippingMethodByCartIdGetRequest $request) {
                static::assertSame(ShippingMethodCollection::class, $request->getResultClass());
                static::assertSame(
                    'shipping-methods?cartId=cart-1',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $shippingMethodRepo = $this->getShippingMethodRepository();
        $shippingMethodRepo->getShippingMethodsByCart('en', 'cart-1');
    }

    public function testGetShippingMethodById()
    {
        $this->client->execute(
            Argument::that(function (ShippingMethodByIdGetRequest $request) {
                static::assertSame(ShippingMethod::class, $request->getResultClass());
                static::assertSame('shippingMethod-1', $request->getId());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $shippingMethodRepo = $this->getShippingMethodRepository();
        $shippingMethodRepo->getShippingMethodById('en', 'shippingMethod-1');
    }
}
