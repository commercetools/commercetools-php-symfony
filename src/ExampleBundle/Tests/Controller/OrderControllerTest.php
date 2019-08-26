<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\LineItem;
use Commercetools\Core\Model\Cart\LineItemCollection;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Payment\PaymentInfo;
use Commercetools\Core\Model\Payment\PaymentReference;
use Commercetools\Core\Model\Payment\PaymentReferenceCollection;
use Commercetools\Symfony\CartBundle\Manager\MeOrderManager;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;
use Commercetools\Symfony\CustomerBundle\Security\User\CtpUser;
use Commercetools\Symfony\ExampleBundle\Controller\OrderController;
use Commercetools\Symfony\StateBundle\Model\ItemStateWrapper;
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
use Twig\Environment;

class OrderControllerTest extends WebTestCase
{
    private $request;
    private $myContainer;
    private $twig;
    private $ctpCient;
    /** @var OrderManager */
    private $orderManager;
    /** @var PaymentManager */
    private $paymentManager;
    private $registry;
    /** @var MeOrderManager */
    private $meOrderManager;

    public function setUp()
    {
        $this->request = $this->prophesize(Request::class);
        $this->myContainer = $this->prophesize(ContainerInterface::class);
        $this->twig = $this->prophesize(Environment::class);
        $this->ctpCient = $this->prophesize(Client::class);
        $this->orderManager = $this->prophesize(OrderManager::class);
        $this->meOrderManager = $this->prophesize(MeOrderManager::class);
        $this->paymentManager = $this->prophesize(PaymentManager::class);
        $this->registry = $this->prophesize(Registry::class);

        $this->request->getLocale()->willReturn('en')->shouldBeCalledOnce();

        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();
    }

    public function testIndexAction()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);

        $this->meOrderManager->getOrdersForUser('en')->willReturn('foo')->shouldBeCalledOnce();

        $controller = new OrderController($this->orderManager->reveal(), $this->registry->reveal(), $this->paymentManager->reveal(), $this->meOrderManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->indexAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testShowOrderActionWithError()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::type('string'), Argument::type('string'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();

        $this->orderManager->getOrderForUser('en', 'order-1', $user->reveal(), 'baz')->willReturn('foo')->shouldBeCalledOnce();

        $controller = new OrderController($this->orderManager->reveal(), $this->registry->reveal(), $this->paymentManager->reveal(), $this->meOrderManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->showOrderAction($this->request->reveal(), $session->reveal(), $user->reveal(), 'order-1');

        $this->assertTrue($response->isOk());
    }

    public function testShowOrderAction()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $paymentReferenceCollection = PaymentReferenceCollection::of()->add(PaymentReference::ofId('id-1'));

        $paymentInfo = $this->prophesize(PaymentInfo::class);
        $paymentInfo->getPayments()->willReturn($paymentReferenceCollection)->shouldBeCalledOnce();

        $order = $this->prophesize(Order::class);
        $order->getPaymentInfo()->willReturn($paymentInfo->reveal())->shouldBeCalledTimes(2);

        $this->orderManager->getOrderForUser('en', 'order-1', $user->reveal(), 'baz')->willReturn($order)->shouldBeCalledOnce();
        $this->paymentManager->getMultiplePayments('en', ['id-1'])->willReturn('foo')->shouldBeCalledOnce();

        $controller = new OrderController($this->orderManager->reveal(), $this->registry->reveal(), $this->paymentManager->reveal(), $this->meOrderManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->showOrderAction($this->request->reveal(), $session->reveal(), $user->reveal(), 'order-1');

        $this->assertTrue($response->isOk());
    }

    public function testUpdateLineItemAction()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $this->request->get('lineItemId')->willReturn('line-item-1')->shouldBeCalledTimes(2);
        $this->request->get('fromState')->willReturn('state-1')->shouldBeCalledTimes(1);
        $this->request->get('toState')->willReturn('state-2')->shouldBeCalledTimes(2);
        $this->request->get('quantity')->willReturn('3')->shouldBeCalledTimes(1);

        $lineItems = LineItemCollection::of()->add(LineItem::of()->setId('line-item-1'));

        $order = $this->prophesize(Order::class);
        $order->getLineItems()->willReturn($lineItems)->shouldBeCalled();

        $this->orderManager->getOrderForUser('en', 'order-1', $user->reveal(), 'baz')->willReturn($order)->shouldBeCalledOnce();

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(ItemStateWrapper::class), 'state-2')->willReturn(true)->shouldBeCalledOnce();
        $workflow->apply(Argument::type(ItemStateWrapper::class), 'state-2')->willReturn(true)->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_order', ['orderId' => 'order-1'], 1)->willReturn('foo')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->has('templating')->shouldNotBeCalled();
        $this->myContainer->has('twig')->shouldNotBeCalled();
        $this->myContainer->get('twig')->shouldNotBeCalled();

        $this->registry->get(Argument::type(ItemStateWrapper::class))->willReturn($workflow->reveal())->shouldBeCalledOnce();

        $controller = new OrderController($this->orderManager->reveal(), $this->registry->reveal(), $this->paymentManager->reveal(), $this->meOrderManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updateLineItemAction($this->request->reveal(), $session->reveal(), 'order-1', $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testUpdateLineItemActionWithCustomLineItem()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $this->request->get('lineItemId')->willReturn(false)->shouldBeCalledTimes(1);
        $this->request->get('customLineItemId')->willReturn('custom-line-item-1')->shouldBeCalledTimes(2);
        $this->request->get('fromState')->willReturn('state-1')->shouldBeCalledTimes(1);
        $this->request->get('toState')->willReturn('state-2')->shouldBeCalledTimes(2);
        $this->request->get('quantity')->willReturn('3')->shouldBeCalledTimes(1);

        $customLineItems = LineItemCollection::of()->add(LineItem::of()->setId('custom-line-item-1'));

        $order = $this->prophesize(Order::class);
        $order->getCustomLineItems()->willReturn($customLineItems)->shouldBeCalled();

        $this->orderManager->getOrderForUser('en', 'order-1', $user->reveal(), 'baz')->willReturn($order)->shouldBeCalledOnce();

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(ItemStateWrapper::class), 'state-2')->willReturn(true)->shouldBeCalledOnce();
        $workflow->apply(Argument::type(ItemStateWrapper::class), 'state-2')->willReturn(true)->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_order', ['orderId' => 'order-1'], 1)->willReturn('foo')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->has('templating')->shouldNotBeCalled();
        $this->myContainer->has('twig')->shouldNotBeCalled();
        $this->myContainer->get('twig')->shouldNotBeCalled();

        $this->registry->get(Argument::type(ItemStateWrapper::class))->willReturn($workflow->reveal())->shouldBeCalledOnce();

        $controller = new OrderController($this->orderManager->reveal(), $this->registry->reveal(), $this->paymentManager->reveal(), $this->meOrderManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updateLineItemAction($this->request->reveal(), $session->reveal(), 'order-1', $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testUpdateLineItemActionWithError()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $this->request->get('lineItemId')->willReturn('line-item-1')->shouldBeCalledTimes(2);
        $this->request->get('fromState')->willReturn('state-1')->shouldBeCalledTimes(1);
        $this->request->get('toState')->willReturn('state-2')->shouldBeCalledTimes(1);
        $this->request->get('quantity')->willReturn('3')->shouldBeCalledTimes(1);

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::type('string'), Argument::type('string'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();

        $lineItems = LineItemCollection::of()->add(LineItem::of()->setId('line-item-1'));

        $order = $this->prophesize(Order::class);
        $order->getLineItems()->willReturn($lineItems)->shouldBeCalled();

        $this->orderManager->getOrderForUser('en', 'order-1', $user->reveal(), 'baz')->willReturn($order)->shouldBeCalledOnce();

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(ItemStateWrapper::class), 'state-2')->shouldBeCalled();

        $this->registry->get(Argument::type(ItemStateWrapper::class))->willReturn($workflow->reveal())->shouldBeCalled();

        $controller = new OrderController($this->orderManager->reveal(), $this->registry->reveal(), $this->paymentManager->reveal(), $this->meOrderManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updateLineItemAction($this->request->reveal(), $session->reveal(), 'order-1', $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testUpdateLineItemActionWithoutItem()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $this->request->get('lineItemId')->willReturn(false)->shouldBeCalledTimes(1);
        $this->request->get('customLineItemId')->willReturn(false)->shouldBeCalledTimes(1);

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::type('string'), Argument::type('string'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();

        $lineItems = LineItemCollection::of()->add(LineItem::of()->setId('line-item-1'));

        $order = $this->prophesize(Order::class);
        $order->getLineItems()->willReturn($lineItems)->shouldNotBeCalled();

        $this->orderManager->getOrderForUser('en', 'order-1', $user->reveal(), 'baz')->willReturn($order)->shouldBeCalledOnce();

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(ItemStateWrapper::class), 'state-2')->shouldNotBeCalled();

        $this->registry->get(Argument::type(ItemStateWrapper::class))->shouldNotBeCalled();

        $controller = new OrderController($this->orderManager->reveal(), $this->registry->reveal(), $this->paymentManager->reveal(), $this->meOrderManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updateLineItemAction($this->request->reveal(), $session->reveal(), 'order-1', $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testUpdateLineItemActionWithoutWorkflow()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $this->request->get('lineItemId')->willReturn('line-item-1')->shouldBeCalledTimes(2);
        $this->request->get('fromState')->willReturn('state-1')->shouldBeCalledTimes(1);
        $this->request->get('quantity')->willReturn('3')->shouldBeCalledTimes(1);


        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::type('string'), Argument::type('string'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();

        $lineItems = LineItemCollection::of()->add(LineItem::of()->setId('line-item-1'));

        $order = $this->prophesize(Order::class);
        $order->getLineItems()->willReturn($lineItems)->shouldBeCalled();

        $this->orderManager->getOrderForUser('en', 'order-1', $user->reveal(), 'baz')->willReturn($order)->shouldBeCalledOnce();

        $this->registry->get(Argument::type(ItemStateWrapper::class))->will(function () {
            throw new InvalidArgumentException();
        })->shouldBeCalledOnce();

        $controller = new OrderController($this->orderManager->reveal(), $this->registry->reveal(), $this->paymentManager->reveal(), $this->meOrderManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updateLineItemAction($this->request->reveal(), $session->reveal(), 'order-1', $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testUpdateOrderAction()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $order = $this->prophesize(Order::class);

        $this->orderManager->getOrderForUser('en', 'order-1', $user->reveal(), 'baz')->willReturn($order)->shouldBeCalledOnce();

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(Order::class), 'state-2')->willReturn(true)->shouldBeCalledOnce();
        $workflow->apply(Argument::type(Order::class), 'state-2')->willReturn(true)->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_order', ['orderId' => 'order-1'], 1)->willReturn('foo')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->has('templating')->shouldNotBeCalled();
        $this->myContainer->has('twig')->shouldNotBeCalled();
        $this->myContainer->get('twig')->shouldNotBeCalled();

        $this->registry->get(Argument::type(Order::class))->willReturn($workflow->reveal())->shouldBeCalledOnce();

        $controller = new OrderController($this->orderManager->reveal(), $this->registry->reveal(), $this->paymentManager->reveal(), $this->meOrderManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updateOrderAction($this->request->reveal(), $session->reveal(), $user->reveal(), 'order-1', 'state-2');

        $this->assertTrue($response->isRedirect());
    }

    public function testUpdateOrderActionWithoutWorkflow()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::type('string'), Argument::type('string'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();

        $order = $this->prophesize(Order::class);

        $this->orderManager->getOrderForUser('en', 'order-1', $user->reveal(), 'baz')->willReturn($order)->shouldBeCalledOnce();

        $this->registry->get(Argument::type(Order::class))->will(function () {
            throw new InvalidArgumentException();
        })->shouldBeCalledOnce();

        $controller = new OrderController($this->orderManager->reveal(), $this->registry->reveal(), $this->paymentManager->reveal(), $this->meOrderManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updateOrderAction($this->request->reveal(), $session->reveal(), $user->reveal(), 'order-1', 'state-2');

        $this->assertTrue($response->isOk());
    }

    public function testUpdateOrderActionWithError()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::type('string'), Argument::type('string'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();

        $order = $this->prophesize(Order::class);

        $this->orderManager->getOrderForUser('en', 'order-1', $user->reveal(), 'baz')->willReturn($order)->shouldBeCalledOnce();

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(Order::class), 'state-2')->shouldBeCalled();

        $this->registry->get(Argument::type(Order::class))->willReturn($workflow->reveal())->shouldBeCalled();

        $controller = new OrderController($this->orderManager->reveal(), $this->registry->reveal(), $this->paymentManager->reveal(), $this->meOrderManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updateOrderAction($this->request->reveal(), $session->reveal(), $user->reveal(), 'order-1', 'state-2');

        $this->assertTrue($response->isOk());
    }
}
