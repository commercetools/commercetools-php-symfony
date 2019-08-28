<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Cart\ShippingInfo;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\ShippingMethod\ShippingMethod;
use Commercetools\Core\Model\ShippingMethod\ShippingMethodCollection;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Carts\Command\CartSetBillingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingMethodAction;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Manager\MeCartManager;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Manager\ShippingMethodManager;
use Commercetools\Symfony\CartBundle\Model\CartUpdateBuilder;
use Commercetools\Symfony\CustomerBundle\Security\User\CtpUser;
use Commercetools\Symfony\CustomerBundle\Security\User\User;
use Commercetools\Symfony\ExampleBundle\Controller\CheckoutController;
use Commercetools\Symfony\ExampleBundle\Entity\CartEntity;
use Commercetools\Symfony\StateBundle\Model\CtpMarkingStore\CtpMarkingStoreOrderState;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Environment;

class CheckoutControllerTest extends WebTestCase
{
    private $request;
    private $myContainer;
    private $twig;
    private $ctpClient;
    /** @var CartManager */
    private $cartManager;
    /** @var ShippingMethodManager */
    private $shippingMethodManager;
    /** @var OrderManager */
    private $orderManager;
    /** @var MeCartManager */
    private $meCartManager;

    public function setUp()
    {
        $this->request = $this->prophesize(Request::class);
        $this->myContainer = $this->prophesize(ContainerInterface::class);
        $this->twig = $this->prophesize(Environment::class);
        $this->ctpClient = $this->prophesize(Client::class);
        $this->cartManager = $this->prophesize(CartManager::class);
        $this->shippingMethodManager = $this->prophesize(ShippingMethodManager::class);
        $this->orderManager = $this->prophesize(OrderManager::class);
        $this->meCartManager = $this->prophesize(MeCartManager::class);

        $this->request->getLocale()->willReturn('en')->shouldBeCalledOnce();
    }

    public function testSigninAction()
    {
        $authorizationChecker = $this->prophesize(MockAuthorizationChecker::class);
        $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')->willReturn(false)->shouldBeCalledOnce();

        $authenticationUtils = $this->prophesize(AuthenticationUtils::class);
        $authenticationUtils->getLastAuthenticationError()->willReturn('foo')->shouldBeCalledOnce();
        $authenticationUtils->getLastUsername()->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('security.authorization_checker')->willReturn($authorizationChecker->reveal())->shouldBeCalledOnce();

        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();
        $this->request->getLocale()->willReturn('en')->shouldNotBeCalled();

        $controller = new CheckoutController($this->cartManager->reveal(), $this->shippingMethodManager->reveal(), $this->orderManager->reveal(), $this->meCartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->signinAction($authenticationUtils->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testSigninActionAlreadySignedIn()
    {
        $authorizationChecker = $this->prophesize(MockAuthorizationChecker::class);
        $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')->willReturn(true)->shouldBeCalledOnce();

        $this->request->getLocale()->willReturn('en')->shouldNotBeCalled();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_checkout_address', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->get('security.authorization_checker')->willReturn($authorizationChecker->reveal())->shouldBeCalledOnce();

        $authenticationUtils = $this->prophesize(AuthenticationUtils::class);

        $controller = new CheckoutController($this->cartManager->reveal(), $this->shippingMethodManager->reveal(), $this->orderManager->reveal(), $this->meCartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->signinAction($authenticationUtils->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testShippingMethodActionSubmitted()
    {
        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function () {
                return $this;
            })->shouldBeCalled();
        $form->isSubmitted()->willReturn(true)->shouldBeCalledOnce();
        $form->isValid()->willReturn(true)->shouldBeCalledOnce();
        $form->get('name')->will(function () {
            return $this;
        })->shouldBeCalledOnce();
        $form->getData()->willReturn('foobar')->shouldBeCalledOnce();

        $formBuilder = $this->prophesize(FormBuilder::class);
        $formBuilder->add(Argument::type('string'), Argument::type('string'), Argument::type('array'))
            ->will(function () {
                return $this;
            })->shouldBeCalledTimes(1);
        $formBuilder->add(Argument::type('string'), Argument::type('string'))
            ->will(function () {
                return $this;
            })->shouldBeCalledTimes(1);
        $formBuilder->getForm()->willReturn($form)->shouldBeCalled();

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->createBuilder(Argument::is(FormType::class), ["name" => "sh-mt-name-1"], [])
            ->willReturn($formBuilder->reveal())->shouldBeCalled();

        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $cart = Cart::of()->setId('cart-id-1')->setShippingInfo(ShippingInfo::of()->setShippingMethodName('sh-mt-name-1'));

        $cartUpdateBuilder = $this->prophesize(CartUpdateBuilder::class);
        $cartUpdateBuilder->addAction(Argument::type(CartSetShippingMethodAction::class))->will(function () {
            return $this;
        })->shouldBeCalled();
        $cartUpdateBuilder->flush()->willReturn($cart)->shouldBeCalled();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_checkout_confirm', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $shippingMethod = ShippingMethodCollection::of()->add(ShippingMethod::of()->setName('sh-mtd')->setId('sh-mtd-1'));

        $this->shippingMethodManager->getShippingMethodsByCart('en', 'cart-id-1')->willReturn($shippingMethod)->shouldBeCalledOnce();
        $this->meCartManager->getCart('en')->willReturn($cart)->shouldBeCalledOnce();
        $this->meCartManager->update(Argument::type(Cart::class))->willReturn($cartUpdateBuilder->reveal())->shouldBeCalledOnce();

        $controller = new CheckoutController($this->cartManager->reveal(), $this->shippingMethodManager->reveal(), $this->orderManager->reveal(), $this->meCartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->shippingMethodAction($this->request->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testShippingMethod()
    {
        $user = $this->prophesize(CtpUser::class);

        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function () {
                return $this;
            })->shouldBeCalled();
        $form->isSubmitted()->willReturn(false)->shouldBeCalledOnce();
        $form->createView()->shouldBeCalledOnce();

        $formBuilder = $this->prophesize(FormBuilder::class);
        $formBuilder->add(Argument::type('string'), Argument::type('string'), Argument::type('array'))
            ->will(function () {
                return $this;
            })->shouldBeCalledTimes(1);
        $formBuilder->add(Argument::type('string'), Argument::type('string'))
            ->will(function () {
                return $this;
            })->shouldBeCalledTimes(1);
        $formBuilder->getForm()->willReturn($form)->shouldBeCalled();

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->createBuilder(Argument::is(FormType::class), ["name" => "sh-mt-name-1"], [])
            ->willReturn($formBuilder->reveal())->shouldBeCalled();

        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $cart = Cart::of()->setId('cart-id-1')->setShippingInfo(ShippingInfo::of()->setShippingMethodName('sh-mt-name-1'));

        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $shippingMethod = ShippingMethodCollection::of()->add(ShippingMethod::of()->setName('sh-mtd')->setId('sh-mtd-1'));

        $this->shippingMethodManager->getShippingMethodsByCart('en', 'cart-id-1')->willReturn($shippingMethod)->shouldBeCalledOnce();
        $this->meCartManager->getCart('en')->willReturn($cart)->shouldBeCalledOnce();

        $controller = new CheckoutController($this->cartManager->reveal(), $this->shippingMethodManager->reveal(), $this->orderManager->reveal(), $this->meCartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->shippingMethodAction($this->request->reveal(), $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testShippingMethodWithoutCart()
    {
        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_cart', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $shippingMethod = ShippingMethodCollection::of()->add(ShippingMethod::of()->setName('sh-mtd')->setId('sh-mtd-1'));

        $this->shippingMethodManager->getShippingMethodsByCart('en', null)->willReturn($shippingMethod)->shouldBeCalledOnce();
        $this->meCartManager->getCart('en')->willReturn(Cart::of())->shouldBeCalledOnce();

        $controller = new CheckoutController($this->cartManager->reveal(), $this->shippingMethodManager->reveal(), $this->orderManager->reveal(), $this->meCartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->shippingMethodAction($this->request->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testReviewOrderDetailsActionCartNotFound()
    {
        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_cart', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $controller = new CheckoutController($this->cartManager->reveal(), $this->shippingMethodManager->reveal(), $this->orderManager->reveal(), $this->meCartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->reviewOrderDetailsAction($this->request->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testReviewOrderDetailsAction()
    {
        $user = $this->prophesize(CtpUser::class);
        $cart = Cart::of()->setId('cart-1');

        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $this->meCartManager->getCart('en')->willReturn($cart)->shouldBeCalledOnce();

        $controller = new CheckoutController($this->cartManager->reveal(), $this->shippingMethodManager->reveal(), $this->orderManager->reveal(), $this->meCartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->reviewOrderDetailsAction($this->request->reveal(), $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testPlaceCartToOrderActionWithCartNotFound()
    {
        $session = $this->prophesize(Session::class);
        $session->get('cart.id')->willReturn('cart-1')->shouldBeCalledOnce();
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $markingStoreOrderState = $this->prophesize(CtpMarkingStoreOrderState::class);

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_cart', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->cartManager->getCart('en', 'cart-1', null, 'baz')->willReturn(null)->shouldBeCalledOnce();

        $controller = new CheckoutController($this->cartManager->reveal(), $this->shippingMethodManager->reveal(), $this->orderManager->reveal(), $this->meCartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
//        $response = $controller->placeCartToOrderAction($this->request->reveal(), $session->reveal(), $markingStoreOrderState->reveal());
        $response = $controller->placeCartToOrderAction($this->request->reveal(), $session->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testPlaceCartToOrderAction()
    {
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();
        $session->get('cart.id')->willReturn('cart-1')->shouldBeCalledOnce();

//        $stateReference = StateReference::ofId('state-1');

//        $markingStoreOrderState = $this->prophesize(CtpMarkingStoreOrderState::class);
//        $markingStoreOrderState->getStateReferenceOfInitial()->willReturn($stateReference)->shouldBeCalledOnce();

        $user = $this->prophesize(CtpUser::class);
        $cart = Cart::of()->setId('cart-1');
        $order = Order::of()->setId('order-2');

        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $this->cartManager->getCart('en', 'cart-1', Argument::type(CtpUser::class), 'baz')->willReturn($cart)->shouldBeCalledOnce();
//        $this->orderManager->createOrderFromCart('en', Argument::type(Cart::class), Argument::type(StateReference::class))->willReturn($order)->shouldBeCalledOnce();
        $this->orderManager->createOrderFromCart('en', Argument::type(Cart::class))->willReturn($order)->shouldBeCalledOnce();

        $controller = new CheckoutController($this->cartManager->reveal(), $this->shippingMethodManager->reveal(), $this->orderManager->reveal(), $this->meCartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
//        $response = $controller->placeCartToOrderAction($this->request->reveal(), $session->reveal(), $markingStoreOrderState->reveal(), $user->reveal());
        $response = $controller->placeCartToOrderAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testSetAddressActionWithNullCart()
    {
        $this->meCartManager->getCart('en')->willReturn(Cart::of())->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_cart', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $controller = new CheckoutController($this->cartManager->reveal(), $this->shippingMethodManager->reveal(), $this->orderManager->reveal(), $this->meCartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->setAddressAction($this->request->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testSetAddressActionSubmitted()
    {
        $user = $this->prophesize(User::class);
        $user->getDefaultShippingAddress()->willReturn(Address::of()->setCountry('FR'))->shouldBeCalled();

        $cart = Cart::of()
            ->setId('cart-1')
            ->setShippingAddress(Address::of()->setCountry('DE'));


        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function () {
                return $this;
            })->shouldBeCalled();
        $form->isSubmitted()->willReturn(true)->shouldBeCalledOnce();
        $form->isValid()->willReturn(true)->shouldBeCalledOnce();
        $form->get(Argument::type('string'))->will(function () {
            return $this;
        })->shouldBeCalledTimes(3);
        $form->get('shippingAddress')->will(function () {
            $this->getData()->willReturn(['country' => 'DE']);
            return $this;
        })->shouldBeCalledOnce();
        $form->getData()->willReturn('foobar')->shouldBeCalledTimes(3);

        $formBuilder = $this->prophesize(FormBuilder::class);
        $formBuilder->add(Argument::type('string'), Argument::type('string'), Argument::type('array'))
            ->will(function () {
                return $this;
            })->shouldBeCalledTimes(1);
        $formBuilder->add(Argument::type('string'), Argument::type('string'))
            ->will(function () {
                return $this;
            })->shouldBeCalledTimes(3);
        $formBuilder->getForm()->willReturn($form)->shouldBeCalled();

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->createBuilder(Argument::is(FormType::class), Argument::type(CartEntity::class), [])
            ->willReturn($formBuilder->reveal())->shouldBeCalled();

        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $cartUpdateBuilder = $this->prophesize(CartUpdateBuilder::class);
        $cartUpdateBuilder->setShippingAddress(Argument::type(CartSetShippingAddressAction::class))->will(function () {
            return $this;
        })->shouldBeCalled();
        $cartUpdateBuilder->setBillingAddress(Argument::type(CartSetBillingAddressAction::class))->will(function () {
            return $this;
        })->shouldBeCalled();
        $cartUpdateBuilder->flush()->willReturn($cart)->shouldBeCalled();

        $this->meCartManager->getCart('en')->willReturn($cart)->shouldBeCalledOnce();
        $this->meCartManager->update(Argument::type(Cart::class))->willReturn($cartUpdateBuilder->reveal())->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_checkout_shipping', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $controller = new CheckoutController($this->cartManager->reveal(), $this->shippingMethodManager->reveal(), $this->orderManager->reveal(), $this->meCartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->setAddressAction($this->request->reveal(), $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testSetAddressActionNotSubmitted()
    {
        $user = $this->prophesize(User::class);
        $user->getDefaultShippingAddress()->willReturn(Address::of()->setCountry('FR'))->shouldBeCalled();

        $cart = Cart::of()
            ->setId('cart-1')
            ->setShippingAddress(Address::of()->setCountry('DE'));

        $this->meCartManager->getCart('en')->willReturn($cart)->shouldBeCalledOnce();

        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function () {
                return $this;
            })->shouldBeCalled();
        $form->isSubmitted()->willReturn(false)->shouldBeCalledOnce();
        $form->createView()->shouldBeCalledOnce();

        $formBuilder = $this->prophesize(FormBuilder::class);
        $formBuilder->add(Argument::type('string'), Argument::type('string'), Argument::type('array'))
            ->will(function () {
                return $this;
            })->shouldBeCalledTimes(1);
        $formBuilder->add(Argument::type('string'), Argument::type('string'))
            ->will(function () {
                return $this;
            })->shouldBeCalledTimes(3);
        $formBuilder->getForm()->willReturn($form)->shouldBeCalled();

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->createBuilder(Argument::is(FormType::class), Argument::type(CartEntity::class), [])
            ->willReturn($formBuilder->reveal())->shouldBeCalled();

        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $controller = new CheckoutController($this->cartManager->reveal(), $this->shippingMethodManager->reveal(), $this->orderManager->reveal(), $this->meCartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->setAddressAction($this->request->reveal(), $user->reveal());

        $this->assertTrue($response->isOk());
    }
}

//phpcs:disable
class MockAuthorizationChecker
{
    public function isGranted($attributes, $subject = null)
    {
        return true;
    }
}
