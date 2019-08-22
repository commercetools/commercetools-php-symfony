<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Model\Repository;

use Commercetools\Core\Client\HttpClient;
use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Orders\Command\OrderSetOrderNumberAction;
use Commercetools\Core\Request\Orders\OrderCreateFromCartRequest;
use Commercetools\Core\Request\Orders\OrderDeleteRequest;
use Commercetools\Core\Request\Orders\OrderQueryRequest;
use Commercetools\Core\Request\Orders\OrderUpdateRequest;
use Commercetools\Core\Response\ResourceResponse;
use Commercetools\Symfony\CartBundle\Model\Repository\OrderRepository;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\CustomerBundle\Security\User\User;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Cache\Tests\Fixtures\ExternalAdapter;

class OrderRepositoryTest extends TestCase
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

    private function getOrderRepository()
    {
        return new OrderRepository(
            false,
            $this->cache,
            $this->client->reveal(),
            $this->mapperFactory->reveal()
        );
    }

    public function testGetOrdersForAnonymous()
    {
        $this->client->execute(
            Argument::that(function (OrderQueryRequest $request) {
                static::assertSame(
                    'orders?sort=createdAt+desc&where=anonymousId+%3D+%22anon-1%22',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $orderRepository = $this->getOrderRepository();
        $orderRepository->getOrders('en', null, 'anon-1');
    }

    public function testGetOrdersForCustomer()
    {
        $this->client->execute(
            Argument::that(function (OrderQueryRequest $request) {
                static::assertSame(
                    'orders?sort=createdAt+desc&where=customerId+%3D+%22user-1%22',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('user-1')->shouldBeCalled();

        $orderRepository = $this->getOrderRepository();
        $orderRepository->getOrders('en', $user->reveal());
    }

    public function testGetOrderForCustomer()
    {
        $this->client->execute(
            Argument::that(function (OrderQueryRequest $request) {
                static::assertSame(
                    'orders?where=id+%3D+%22order-1%22+and+customerId+%3D+%22user-1%22',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('user-1')->shouldBeCalled();

        $orderRepository = $this->getOrderRepository();
        $orderRepository->getOrder('en', 'order-1', $user->reveal());
    }

    public function testGetOrderForAnonymous()
    {
        $this->client->execute(
            Argument::that(function (OrderQueryRequest $request) {
                static::assertSame(
                    'orders?where=id+%3D+%22order-1%22+and+anonymousId+%3D+%22anon-1%22',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $orderRepository = $this->getOrderRepository();
        $orderRepository->getOrder('en', 'order-1', null, 'anon-1');
    }

    public function testGetOrderWithId()
    {
        $this->client->execute(
            Argument::that(function (OrderQueryRequest $request) {
                static::assertSame(
                    'orders?where=id+%3D+%22order-1%22',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $orderRepository = $this->getOrderRepository();
        $orderRepository->getOrder('en', 'order-1');
    }

    public function testGetOrdersWithoutUser()
    {
        $this->client->execute(
            Argument::that(function (OrderQueryRequest $request) {
                static::assertStringStartsWith('orders', (string)$request->httpRequest()->getUri());
                static::assertContains('sort=createdAt+desc', (string)$request->httpRequest()->getUri());
                static::assertNotContains('where=', (string)$request->httpRequest()->getUri());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $orderRepository = $this->getOrderRepository();
        $orderRepository->getOrders('en');
    }

    public function testCreateOrderFromCart()
    {
        $this->client->execute(
            Argument::that(function (OrderCreateFromCartRequest $request) {
                static::assertSame(Order::class, $request->getResultClass());
                static::assertSame('cart-1', $request->getCartId());
                static::assertSame(1, $request->getVersion());
                static::assertInstanceOf(StateReference::class, $request->getState());
                static::assertSame('state-1', $request->getState()->getId());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $cart = Cart::of()->setId('cart-1')->setVersion(1);
        $state = StateReference::of()->setId('state-1');

        $orderRepository = $this->getOrderRepository();
        $orderRepository->createOrderFromCart('en', $cart, $state);
    }

    public function testUpdateOrder()
    {
        $this->client->execute(
            Argument::that(function (OrderUpdateRequest $request) {
                static::assertSame(Order::class, $request->getResultClass());
                static::assertSame('order-1', $request->getId());
                static::assertSame(1, $request->getVersion());

                $action = current($request->getActions());
                static::assertInstanceOf(OrderSetOrderNumberAction::class, $action);
                static::assertSame('new-order-number', $action->getOrderNumber());

                static::assertSame(
                    'orders/order-1?foo=bar',
                    (string)$request->httpRequest()->getUri()
                );

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $order = Order::of()->setId('order-1')->setVersion(1);
        $actions = [
            OrderSetOrderNumberAction::of()->setOrderNumber('new-order-number')
        ];
        $params = new QueryParams();
        $params->add('foo', 'bar');

        $orderRepository = $this->getOrderRepository();
        $orderRepository->update($order, $actions, $params);
    }

    public function testGetOrderFromPaymentForAnonymous()
    {
        $this->client->execute(
            Argument::that(function (OrderQueryRequest $request) {
                static::assertSame(
                    'orders?where=paymentInfo%28payments%28id+%3D+%22payment-1%22%29%29+and+anonymousId+%3D+%22anon-1%22',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $orderRepository = $this->getOrderRepository();
        $orderRepository->getOrdersFromPayment('en', 'payment-1', null, 'anon-1');
    }

    public function testGetOrderFromPaymentForCustomer()
    {
        $this->client->execute(
            Argument::that(function (OrderQueryRequest $request) {
                static::assertStringStartsWith('orders', (string)$request->httpRequest()->getUri());
                static::assertContains('where=', (string)$request->httpRequest()->getUri());
                static::assertContains('paymentInfo%28payments%28id+%3D+%22payment-1%22%29%29', (string)$request->httpRequest()->getUri());
                static::assertContains('customerId+%3D+%22user-1%22', (string)$request->httpRequest()->getUri());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('user-1')->shouldBeCalledOnce();

        $orderRepository = $this->getOrderRepository();
        $orderRepository->getOrdersFromPayment('en', 'payment-1', $user->reveal());
    }

    public function testGetOrderFromPaymentWithoutUser()
    {
        $this->client->execute(
            Argument::that(function (OrderQueryRequest $request) {
                static::assertStringStartsWith('orders', (string)$request->httpRequest()->getUri());
                static::assertContains('where=', (string)$request->httpRequest()->getUri());
                static::assertContains('paymentInfo%28payments%28id+%3D+%22payment-1%22%29%29', (string)$request->httpRequest()->getUri());
                static::assertNotContains('customerId', (string)$request->httpRequest()->getUri());
                static::assertNotContains('anonymousId', (string)$request->httpRequest()->getUri());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $orderRepository = $this->getOrderRepository();
        $orderRepository->getOrdersFromPayment('en', 'payment-1');
    }

    public function testDeleteOrder()
    {
        $this->client->execute(
            Argument::that(function (OrderDeleteRequest $request) {
                static::assertSame(Order::class, $request->getResultClass());
                static::assertSame('order-1', $request->getId());
                static::assertSame(1, $request->getVersion());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $orderRepository = $this->getOrderRepository();
        $orderRepository->delete(Order::of()->setId('order-1')->setVersion(1));
    }
}
