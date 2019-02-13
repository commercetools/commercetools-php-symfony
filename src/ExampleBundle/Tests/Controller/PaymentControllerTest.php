<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Error\ApiError;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Payment\PaymentStatus;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;
use Commercetools\Symfony\CtpBundle\Service\CustomTypeProvider;
use Commercetools\Symfony\CustomerBundle\Security\User\User;
use Commercetools\Symfony\ExampleBundle\Controller\PaymentController;
use Commercetools\Symfony\StateBundle\Model\CtpMarkingStore\CtpMarkingStorePaymentState;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Workflow\Exception\InvalidArgumentException;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Workflow;

class PaymentControllerTest extends WebTestCase
{
    private $request;
    private $myContainer;
    private $twig;
    private $client;
    /** @var PaymentManager */
    private $paymentManager;
    private $registry;

    public function setUp()
    {
        $this->request = $this->prophesize(Request::class);
        $this->myContainer = $this->prophesize(ContainerInterface::class);
        $this->twig = $this->prophesize(\Twig_Environment::class);
        $this->client = $this->prophesize(Client::class);
        $this->paymentManager = $this->prophesize(PaymentManager::class);
        $this->registry = $this->prophesize(Registry::class);

        $this->request->getLocale()->willReturn('en')->shouldBeCalledOnce();
    }

    public function testGetPaymentAction()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $this->paymentManager->getPaymentForUser('en', 'payment-1', null, 'baz')
            ->willReturn(Payment::of())->shouldBeCalledOnce();

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->getPaymentAction($this->request->reveal(), $session->reveal(), 'payment-1', 'order-2');

        $this->assertTrue($response->isOk());
    }

    public function testGetPaymentActionWithError()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('Cannot find payment: payment-1'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();

        $this->paymentManager->getPaymentForUser('en', 'payment-1', null, 'baz')
            ->willReturn(null)->shouldBeCalledOnce();

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->getPaymentAction($this->request->reveal(), $session->reveal(), 'payment-1', 'order-2');

        $this->assertTrue($response->isOk());
    }

    public function testCreatePaymentForOrderActionCannotFindOrder()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('Cannot find order: order-1'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $orderManager = $this->prophesize(OrderManager::class);
        $orderManager->getOrderForUser('en', 'order-1', null, 'baz')->willReturn(null)->shouldBeCalledOnce();

        $markingStorePaymentState = $this->prophesize(CtpMarkingStorePaymentState::class);
        $customType = $this->prophesize(CustomTypeProvider::class);

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->createPaymentForOrderAction(
            $this->request->reveal(),
            $session->reveal(),
            $orderManager->reveal(),
            $markingStorePaymentState->reveal(),
            'order-1',
            $customType->reveal()
        );

        $this->assertTrue($response->isOk());
    }

    public function testCreatePaymentForOrderActionCannotCreatePayment()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledTimes(2);

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('generic error message'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $customType = $this->prophesize(CustomTypeProvider::class);
        $customType->getTypeReference('paymentsRelations')->willReturn(null)->shouldBeCalledOnce();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $markingStorePaymentState = $this->prophesize(CtpMarkingStorePaymentState::class);
        $markingStorePaymentState->getStateReferenceOfInitial()->willReturn(StateReference::ofId('state-1'))->shouldBeCalledOnce();

        $payment = new ApiError([]);
        $payment->setMessage('generic error message');

        $order = Order::of()
            ->setTotalPrice(Money::ofCurrencyAndAmount('EUR', 100))
            ->setId('order-1');

        /** @var OrderManager $orderManager */
        $orderManager = $this->prophesize(OrderManager::class);
        $orderManager->getOrderForUser('en', 'order-1', null, 'baz')->willReturn($order)->shouldBeCalledOnce();
        $this->paymentManager->createPaymentForUser(
            'en',
            Argument::type(Money::class),
            null,
            'baz',
            Argument::type(PaymentStatus::class),
            null
        )->willReturn($payment)->shouldBeCalledOnce();

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->createPaymentForOrderAction(
            $this->request->reveal(),
            $session->reveal(),
            $orderManager->reveal(),
            $markingStorePaymentState->reveal(),
            'order-1',
            $customType->reveal()
        );

        $this->assertTrue($response->isOk());
    }

    public function testCreatePaymentForOrderAction()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledTimes(2);

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_order', Argument::type('array'), 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $customType = $this->prophesize(CustomTypeProvider::class);
        $customType->getTypeReference('paymentsRelations')->willReturn(null)->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $markingStorePaymentState = $this->prophesize(CtpMarkingStorePaymentState::class);
        $markingStorePaymentState->getStateReferenceOfInitial()->willReturn(StateReference::ofId('state-1'))->shouldBeCalledOnce();

        $payment = Payment::of()->setId('payment-1');

        $order = Order::of()
            ->setTotalPrice(Money::ofCurrencyAndAmount('EUR', 100))
            ->setId('order-1');

        $orderManager = $this->prophesize(OrderManager::class);
        $orderManager->getOrderForUser('en', 'order-1', null, 'baz')->willReturn($order)->shouldBeCalledOnce();

        $this->paymentManager->createPaymentForUser(
            'en',
            Argument::type(Money::class),
            null,
            'baz',
            Argument::type(PaymentStatus::class),
            null
        )->willReturn($payment)->shouldBeCalledOnce();

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->createPaymentForOrderAction(
            $this->request->reveal(),
            $session->reveal(),
            $orderManager->reveal(),
            $markingStorePaymentState->reveal(),
            'order-1',
            $customType->reveal()
        );

        $this->assertTrue($response->isRedirect());
    }

    public function testCreatePaymentForCartActionCannotFindOrder()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();
        $session->get('cart.id')->willReturn('cart-1')->shouldBeCalledOnce();

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('Error finding cart'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $cartManager = $this->prophesize(CartManager::class);
        $cartManager->getCart('en', 'cart-1', null, 'baz')->willReturn(null)->shouldBeCalledOnce();

        $markingStorePaymentState = $this->prophesize(CtpMarkingStorePaymentState::class);
        $customType = $this->prophesize(CustomTypeProvider::class);

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->createPaymentForCartAction(
            $this->request->reveal(),
            $session->reveal(),
            $cartManager->reveal(),
            $markingStorePaymentState->reveal(),
            $customType->reveal()
        );

        $this->assertTrue($response->isOk());
    }

    public function testCreatePaymentForCartActionCannotCreatePayment()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledTimes(2);
        $session->get('cart.id')->willReturn('cart-1')->shouldBeCalledOnce();

        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('user-1')->shouldBeCalledOnce();

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('generic error message'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $customType = $this->prophesize(CustomTypeProvider::class);
        $customType->getTypeReference('paymentsRelations')->willReturn(null)->shouldBeCalledOnce();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $markingStorePaymentState = $this->prophesize(CtpMarkingStorePaymentState::class);
        $markingStorePaymentState->getStateReferenceOfInitial()->willReturn(StateReference::ofId('state-1'))->shouldBeCalledOnce();

        $payment = new ApiError([]);
        $payment->setMessage('generic error message');

        $cart = Cart::of()
            ->setTotalPrice(Money::ofCurrencyAndAmount('EUR', 100));

        $cartManager = $this->prophesize(CartManager::class);
        $cartManager->getCart('en', 'cart-1', $user->reveal(), 'baz')->willReturn($cart)->shouldBeCalledOnce();
        $this->paymentManager->createPaymentForUser(
            'en',
            Argument::type(Money::class),
            Argument::type(CustomerReference::class),
            'baz',
            Argument::type(PaymentStatus::class),
            null
        )->willReturn($payment)->shouldBeCalledOnce();

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->createPaymentForCartAction(
            $this->request->reveal(),
            $session->reveal(),
            $cartManager->reveal(),
            $markingStorePaymentState->reveal(),
            $customType->reveal(),
            $user->reveal()
        );

        $this->assertTrue($response->isOk());
    }

    public function testCreatePaymentForCartAction()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledTimes(2);
        $session->get('cart.id')->willReturn('cart-1')->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_checkout_confirm', Argument::type('array'), 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $customType = $this->prophesize(CustomTypeProvider::class);
        $customType->getTypeReference('paymentsRelations')->willReturn(null)->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $markingStorePaymentState = $this->prophesize(CtpMarkingStorePaymentState::class);
        $markingStorePaymentState->getStateReferenceOfInitial()->willReturn(StateReference::ofId('state-1'))->shouldBeCalledOnce();

        $payment = Payment::of()->setId('payment-1');

        $cart = Cart::of()
            ->setTotalPrice(Money::ofCurrencyAndAmount('EUR', 100));

        $cartManager = $this->prophesize(CartManager::class);
        $cartManager->getCart('en', 'cart-1', null, 'baz')->willReturn($cart)->shouldBeCalledOnce();

        $this->paymentManager->createPaymentForUser(
            'en',
            Argument::type(Money::class),
            null,
            'baz',
            Argument::type(PaymentStatus::class),
            null
        )->willReturn($payment)->shouldBeCalledOnce();

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->createPaymentForCartAction(
            $this->request->reveal(),
            $session->reveal(),
            $cartManager->reveal(),
            $markingStorePaymentState->reveal(),
            $customType->reveal()
        );

        $this->assertTrue($response->isRedirect());
    }

    public function testUpdatePaymentActionCannotFindPayment()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledTimes(1);

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('Cannot find payment'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $this->paymentManager->getPaymentForUser('en', 'payment-1', null, 'baz')
            ->willReturn(null)->shouldBeCalledOnce();

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updatePaymentAction($this->request->reveal(), $session->reveal(), 'state-2', 'payment-1');

        $this->assertTrue($response->isOk());
    }

    public function testUpdatePaymentActionCannotFindWorkflow()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledTimes(1);

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('Cannot find proper workflow configuration for Payments. Action aborted'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $this->registry->get(Argument::type(Payment::class))->will(function () {
            throw new InvalidArgumentException();
        })->shouldBeCalledOnce();

        $this->paymentManager->getPaymentForUser('en', 'payment-1', null, 'baz')
            ->willReturn(Payment::of())->shouldBeCalledOnce();

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updatePaymentAction($this->request->reveal(), $session->reveal(), 'state-2', 'payment-1');

        $this->assertTrue($response->isOk());
    }

    public function testUpdatePaymentActionPerformWorkflow()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledTimes(1);

        $this->request->get('orderId')->willReturn('order-1')->shouldBeCalledOnce();

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(Payment::class), 'state-2')->willReturn(true)->shouldBeCalledOnce();
        $workflow->apply(Argument::type(Payment::class), 'state-2')->willReturn(true)->shouldBeCalledOnce();

        $this->registry->get(Argument::type(Payment::class))->willReturn($workflow)->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_order', Argument::type('array'), 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $this->paymentManager->getPaymentForUser('en', 'payment-1', null, 'baz')
            ->willReturn(Payment::of())->shouldBeCalledOnce();

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updatePaymentAction($this->request->reveal(), $session->reveal(), 'state-2', 'payment-1');

        $this->assertTrue($response->isRedirect());
    }

    public function testUpdatePaymentActionPerformWorkflowGoToAllOrdersPage()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledTimes(1);

        $this->request->get('orderId')->willReturn(null)->shouldBeCalledOnce();

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(Payment::class), 'state-2')->willReturn(true)->shouldBeCalledOnce();
        $workflow->apply(Argument::type(Payment::class), 'state-2')->willReturn(true)->shouldBeCalledOnce();

        $this->registry->get(Argument::type(Payment::class))->willReturn($workflow)->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_orders_all', Argument::type('array'), 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $this->paymentManager->getPaymentForUser('en', 'payment-1', null, 'baz')
            ->willReturn(Payment::of())->shouldBeCalledOnce();

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updatePaymentAction($this->request->reveal(), $session->reveal(), 'state-2', 'payment-1');

        $this->assertTrue($response->isRedirect());
    }

    public function testUpdatePaymentActionCannotPerformWorkflow()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledTimes(1);

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('Cannot perform this action'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(Payment::class), 'state-2')->willReturn(false)->shouldBeCalledOnce();

        $this->registry->get(Argument::type(Payment::class))->willReturn($workflow)->shouldBeCalledOnce();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $this->paymentManager->getPaymentForUser('en', 'payment-1', null, 'baz')
            ->willReturn(Payment::of())->shouldBeCalledOnce();

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updatePaymentAction($this->request->reveal(), $session->reveal(), 'state-2', 'payment-1');

        $this->assertTrue($response->isOk());
    }

    public function testUpdatePaymentActionPerformWorkflowToCompleted()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledTimes(1);

        $this->request->get('orderId')->willReturn('order-1')->shouldBeCalledOnce();
        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(1);

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(Payment::class), 'toCompleted')->willReturn(true)->shouldBeCalledOnce();
        $workflow->apply(Argument::type(Payment::class), 'toCompleted')->willReturn(true)->shouldBeCalledOnce();

        $this->registry->get(Argument::type(Payment::class))->willReturn($workflow)->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_order', Argument::type('array'), 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $this->paymentManager->getPaymentForUser('en', 'payment-1', null, 'baz')
            ->willReturn(Payment::of()->setId('payment-1'))->shouldBeCalledOnce();

        $controller = new PaymentController($this->client->reveal(), $this->paymentManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updatePaymentAction($this->request->reveal(), $session->reveal(), 'toCompleted', 'payment-1');

        $this->assertTrue($response->isRedirect());
    }
}
