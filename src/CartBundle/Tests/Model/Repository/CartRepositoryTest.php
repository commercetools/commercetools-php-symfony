<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Model\Repository;

use Commercetools\Core\Client;
use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Cart\CartDraft;
use Commercetools\Core\Model\Cart\LineItemDraft;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Core\Request\Carts\CartCreateRequest;
use Commercetools\Core\Request\Carts\CartDeleteRequest;
use Commercetools\Core\Request\Carts\CartQueryRequest;
use Commercetools\Core\Request\Carts\CartUpdateRequest;
use Commercetools\Core\Request\Carts\Command\CartSetCountryAction;
use Commercetools\Core\Response\ResourceResponse;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\CustomerBundle\Security\User\User;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Cache\Tests\Fixtures\ExternalAdapter;

class CartRepositoryTest extends TestCase
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

        $this->client = $this->prophesize(Client::class);
    }

    private function getCartRepository()
    {
        return new CartRepository(
            false,
            $this->cache,
            $this->client->reveal(),
            $this->mapperFactory->reveal()
        );
    }

    public function testGetCartForAnonymous()
    {
        $this->client->execute(
            Argument::that(function (CartQueryRequest $request) {
                static::assertSame(
                    'carts?limit=1&where=cartState+%3D+%22Active%22+and+id+%3D+%22cart-1%22+and+anonymousId+%3D+%22anon-1%22',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();


        $cartRepository = $this->getCartRepository();
        $cartRepository->getCart('en', 'cart-1', null, 'anon-1');
    }

    public function testGetCartForCustomer()
    {
        $this->client->execute(
            Argument::that(function (CartQueryRequest $request) {
                static::assertSame(
                    'carts?limit=1&where=cartState+%3D+%22Active%22+and+id+%3D+%22cart-1%22+and+customerId+%3D+%22user-1%22',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('user-1')->shouldBeCalled();

        $cartRepository = $this->getCartRepository();
        $cartRepository->getCart('en', 'cart-1', $user->reveal());
    }

    public function testCreateCartForAnonymous()
    {
        $this->client->execute(
            Argument::allOf(
                Argument::type(CartCreateRequest::class),
                Argument::that(function (CartCreateRequest $request) {
                    return $request->getObject() instanceof CartDraft;
                }),
                Argument::that(function (CartCreateRequest $request) {
                    return $request->getObject()->getAnonymousId() === 'foo-123';
                }),
                Argument::that(function (CartCreateRequest $request) {
                    return $request->getObject()->getCurrency() === 'EUR';
                }),
                Argument::that(function (CartCreateRequest $request) {
                    return is_null($request->getObject()->getLineItems());
                }),
                Argument::that(function (CartCreateRequest $request) {
                    return is_null($request->getObject()->getCustomerId());
                }),
                Argument::that(function (CartCreateRequest $request) {
                    return $request->getObject()->getCountry() === 'DE';
                })
            ),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $location = Location::of()->setCountry('DE');
        $cartRepository = $this->getCartRepository();
        $cartRepository->createCart('en', 'EUR', $location, null, null, 'foo-123');
    }

    public function testCreateCartForCustomerWithLineItems()
    {
        $this->client->execute(
            Argument::that(function (CartCreateRequest $request) {
                static::assertInstanceOf(CartDraft::class, $request->getObject());
                static::assertSame('customer-123', $request->getObject()->getCustomerId());
                static::assertSame('EUR', $request->getObject()->getCurrency());
                static::assertSame('DE', $request->getObject()->getCountry());
                static::assertNull($request->getObject()->getAnonymousId());
                static::assertNotNull($request->getObject()->getLineItems());
                static::assertSame('sku-123', $request->getObject()->getLineItems()->current()->getSku());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $location = Location::of()->setCountry('DE');
        $lineItemDraftCollection = LineItemDraftCollection::of()->add(
            LineItemDraft::ofSku('sku-123')
        );

        $cartRepository = $this->getCartRepository();
        $cartRepository->createCart('en', 'EUR', $location, $lineItemDraftCollection, 'customer-123');
    }

    public function testUpdateCart()
    {
        $this->client->execute(
            Argument::that(function (CartUpdateRequest $request) {
                $action = current($request->getActions());

                static::assertSame(Cart::class, $request->getResultClass());
                static::assertInstanceOf(CartSetCountryAction::class, $action);
                static::assertSame('US', $action->getCountry());
                static::assertSame('cart-1', $request->getId());
                static::assertSame(1, $request->getVersion());
                static::assertSame(
                    'carts/cart-1?foo=bar',
                    (string)$request->httpRequest()->getUri()
                );

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $params = new QueryParams();
        $params->add('foo', 'bar');

        $cartRepository = $this->getCartRepository();
        $cartRepository->update(Cart::of()->setId('cart-1')->setVersion(1), [
            CartSetCountryAction::of()->setCountry('US')
        ], $params);
    }


    public function testCreateCartWithoutUser()
    {
        $this->client->execute(
            Argument::that(function (CartCreateRequest $request) {
                static::assertInstanceOf(CartDraft::class, $request->getObject());
                static::assertNull($request->getObject()->getCustomerId());
                static::assertSame('EUR', $request->getObject()->getCurrency());
                static::assertSame('DE', $request->getObject()->getCountry());
                static::assertNull($request->getObject()->getAnonymousId());
                static::assertNull($request->getObject()->getLineItems());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $location = Location::of()->setCountry('DE');
        $cartRepository = $this->getCartRepository();
        $cartRepository->createCart('en', 'EUR', $location);
    }

    public function testDeleteCart()
    {
        $this->client->execute(
            Argument::that(function (CartDeleteRequest $request) {
                static::assertSame(Cart::class, $request->getResultClass());
                static::assertSame('cart-1', $request->getId());
                static::assertSame(1, $request->getVersion());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $cartRepository = $this->getCartRepository();
        $cartRepository->delete(Cart::of()->setId('cart-1')->setVersion(1));
    }
}
