<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Core\Request\Carts\Command\CartAddLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartAddShoppingListAction;
use Commercetools\Core\Request\Carts\Command\CartChangeLineItemQuantityAction;
use Commercetools\Core\Request\Carts\Command\CartRemoveLineItemAction;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Model\CartUpdateBuilder;
use Commercetools\Symfony\CustomerBundle\Security\User\CtpUser;
use Commercetools\Symfony\ExampleBundle\Controller\CartController;
use Commercetools\Symfony\ExampleBundle\Entity\ProductEntity;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Twig\Environment;

class CartControllerTest extends WebTestCase
{
    private $request;
    private $myContainer;
    private $twig;
    private $ctpClient;
    /** @var CartManager */
    private $cartManager;

    public function setUp()
    {
        $this->request = $this->prophesize(Request::class);
        $this->myContainer = $this->prophesize(ContainerInterface::class);
        $this->twig = $this->prophesize(Environment::class);
        $this->ctpClient = $this->prophesize(Client::class);
        $this->cartManager = $this->prophesize(CartManager::class);

        $this->request->getLocale()->willReturn('en')->shouldBeCalledOnce();
    }

    public function testIndexAction()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();
        $session->get('cart.id')->willReturn('cart-from-session')->shouldBeCalledOnce();

        $this->cartManager->getCart('en', 'cart-from-session', $user->reveal(), 'baz')->willReturn('foo')->shouldBeCalledOnce();

        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $controller = new CartController($this->ctpClient->reveal(), $this->cartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->indexAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testIndexActionWithoutCart()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();
        $session->get('cart.id')->willReturn(null)->shouldBeCalledOnce();

        $this->cartManager->getCart('en', null, $user->reveal(), 'baz')->willReturn(null)->shouldBeCalledOnce();

        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $controller = new CartController($this->ctpClient->reveal(), $this->cartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->indexAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testAddLineItemAction()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();
        $session->get('cart.id')->willReturn('cart-id-1')->shouldBeCalledOnce();

        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function () {
                return $this;
            })->shouldBeCalled();
        $form->isSubmitted()->willReturn(true)->shouldBeCalledOnce();
        $form->isValid()->willReturn(true)->shouldBeCalledOnce();
        $form->get(Argument::type('string'))->will(function () {
            return $this;
        })->shouldBeCalledTimes(4);
        $form->getData()->willReturn('foo')->shouldBeCalledTimes(4);

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::containingString('AddToCartType'), Argument::type(ProductEntity::class), Argument::type('array'))
            ->willReturn($form->reveal())->shouldBeCalled();

        $cart = Cart::of();

        $cartUpdateBuilder = $this->prophesize(CartUpdateBuilder::class);
        $cartUpdateBuilder->addAction(Argument::type(CartAddLineItemAction::class))->will(function () {
            return $this;
        })->shouldBeCalled();
        $cartUpdateBuilder->flush()->willReturn($cart)->shouldBeCalled();

        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $this->cartManager->getCart('en', 'cart-id-1', $user->reveal(), 'baz')->willReturn(Cart::of())->shouldBeCalledOnce();
        $this->cartManager->update(Argument::type(Cart::class))->willReturn($cartUpdateBuilder->reveal())->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_product', ['slug' => 'foo'], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->has('templating')->shouldNotBeCalled();
        $this->myContainer->has('twig')->shouldNotBeCalled();
        $this->myContainer->get('twig')->shouldNotBeCalled();

        $controller = new CartController($this->ctpClient->reveal(), $this->cartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->addLineItemAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testAddLineItemActionWithoutSubmit()
    {
        $user = $this->prophesize(CtpUser::class);
        $session = $this->prophesize(Session::class);

        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function () {
                return $this;
            })->shouldBeCalled();
        $form->isSubmitted()->willReturn(false)->shouldBeCalledOnce();

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::containingString('AddToCartType'), Argument::type(ProductEntity::class), Argument::type('array'))
            ->willReturn($form->reveal())->shouldBeCalled();

        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $this->request->getLocale()->shouldNotBeCalled();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->has('templating')->shouldNotBeCalled();
        $this->myContainer->has('twig')->shouldNotBeCalled();
        $this->myContainer->get('twig')->shouldNotBeCalled();

        $controller = new CartController($this->ctpClient->reveal(), $this->cartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->addLineItemAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testAddLineItemActionAndCreateCart()
    {
        $user = $this->prophesize(CtpUser::class);
        $user->getId()->willReturn('user-1')->shouldBeCalledOnce();

        $session = $this->prophesize(Session::class);
        $session->get('cart.id')->willReturn(null)->shouldBeCalledOnce();

        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function () {
                return $this;
            })->shouldBeCalled();
        $form->isSubmitted()->willReturn(true)->shouldBeCalledOnce();
        $form->isValid()->willReturn(true)->shouldBeCalledOnce();
        $form->get(Argument::type('string'))->will(function () {
            return $this;
        })->shouldBeCalledTimes(4);
        $form->getData()->willReturn('foo')->shouldBeCalledTimes(4);

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::containingString('AddToCartType'), Argument::type(ProductEntity::class), Argument::type('array'))
            ->willReturn($form->reveal())->shouldBeCalled();

        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $this->cartManager->createCartForUser('en', 'EUR', Argument::type(Location::class), Argument::type(LineItemDraftCollection::class), 'user-1')->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_product', ['slug' => 'foo'], 1)->willReturn('bar')->shouldBeCalledOnce();

        $parameterBag = $this->prophesize(ParameterBag::class);
        $parameterBag->get('commercetools.project_settings.countries')->willReturn(['DE'])->shouldBeCalledOnce();
        $parameterBag->get('commercetools.project_settings.currencies')->willReturn(['EUR'])->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->has('templating')->shouldNotBeCalled();
        $this->myContainer->has('parameter_bag')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('parameter_bag')->willReturn($parameterBag->reveal())->shouldBeCalled();
        $this->myContainer->has('twig')->shouldNotBeCalled();
        $this->myContainer->get('twig')->shouldNotBeCalled();

        $parameterBag = $this->prophesize(ParameterBag::class);

        $controller = new CartController($this->ctpClient->reveal(), $this->cartManager->reveal(), $parameterBag->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->addLineItemAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testAddLineItemActionAndCreateCartForAnonymous()
    {
        $session = $this->prophesize(Session::class);
        $session->get('cart.id')->willReturn(null)->shouldBeCalledOnce();
        $session->getId()->willReturn('session-1')->shouldBeCalledOnce();

        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function () {
                return $this;
            })->shouldBeCalled();
        $form->isSubmitted()->willReturn(true)->shouldBeCalledOnce();
        $form->isValid()->willReturn(true)->shouldBeCalledOnce();
        $form->get(Argument::type('string'))->will(function () {
            return $this;
        })->shouldBeCalledTimes(4);
        $form->getData()->willReturn('foo')->shouldBeCalledTimes(4);

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::containingString('AddToCartType'), Argument::type(ProductEntity::class), Argument::type('array'))
            ->willReturn($form->reveal())->shouldBeCalled();

        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $this->cartManager->createCartForUser('en', 'EUR', Argument::type(Location::class), Argument::type(LineItemDraftCollection::class), null, 'session-1')->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_product', ['slug' => 'foo'], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->has('templating')->shouldNotBeCalled();
        $this->myContainer->has('twig')->shouldNotBeCalled();
        $this->myContainer->get('twig')->shouldNotBeCalled();

        $parameterBag = $this->prophesize(ParameterBag::class);
        $parameterBag->get('commercetools.project_settings.countries')->willReturn(['DE'])->shouldBeCalledOnce();
        $parameterBag->get('commercetools.project_settings.currencies')->willReturn(['EUR'])->shouldBeCalledOnce();

        $this->myContainer->has('parameter_bag')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('parameter_bag')->willReturn($parameterBag->reveal())->shouldBeCalled();

        $controller = new CartController($this->ctpClient->reveal(), $this->cartManager->reveal(), $parameterBag->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->addLineItemAction($this->request->reveal(), $session->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testChangeLineItemAction()
    {
        $session = $this->prophesize(Session::class);
        $session->get('cart.id')->willReturn('cart-id-1')->shouldBeCalledOnce();
        $session->getId()->willReturn('session-1')->shouldBeCalledOnce();

        $user = $this->prophesize(CtpUser::class);

        $cart = Cart::of();

        $cartUpdateBuilder = $this->prophesize(CartUpdateBuilder::class);
        $cartUpdateBuilder->addAction(Argument::type(CartChangeLineItemQuantityAction::class))->will(function () {
            return $this;
        })->shouldBeCalled();
        $cartUpdateBuilder->flush()->willReturn($cart)->shouldBeCalled();

        $this->cartManager->getCart('en', 'cart-id-1', $user->reveal(), 'session-1')->willReturn(Cart::of())->shouldBeCalledOnce();
        $this->cartManager->update(Argument::type(Cart::class))->willReturn($cartUpdateBuilder->reveal())->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_cart', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->has('templating')->shouldNotBeCalled();
        $this->myContainer->has('twig')->shouldNotBeCalled();
        $this->myContainer->get('twig')->shouldNotBeCalled();

        $this->request->get('lineItemId')->willReturn('foo')->shouldBeCalledOnce();
        $this->request->get('quantity')->willReturn('2')->shouldBeCalledOnce();

        $controller = new CartController($this->ctpClient->reveal(), $this->cartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->changeLineItemAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testDeleteLineItemAction()
    {
        $session = $this->prophesize(Session::class);
        $session->get('cart.id')->willReturn('cart-id-1')->shouldBeCalledOnce();
        $session->getId()->willReturn('session-1')->shouldBeCalledOnce();

        $user = $this->prophesize(CtpUser::class);

        $cart = Cart::of();

        $cartUpdateBuilder = $this->prophesize(CartUpdateBuilder::class);
        $cartUpdateBuilder->addAction(Argument::type(CartRemoveLineItemAction::class))->will(function () {
            return $this;
        })->shouldBeCalled();
        $cartUpdateBuilder->flush()->willReturn($cart)->shouldBeCalled();

        $this->cartManager->getCart('en', 'cart-id-1', $user->reveal(), 'session-1')->willReturn(Cart::of())->shouldBeCalledOnce();
        $this->cartManager->update(Argument::type(Cart::class))->willReturn($cartUpdateBuilder->reveal())->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_cart', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->has('templating')->shouldNotBeCalled();
        $this->myContainer->has('twig')->shouldNotBeCalled();
        $this->myContainer->get('twig')->shouldNotBeCalled();

        $this->request->get('lineItemId')->willReturn('foo')->shouldBeCalledOnce();

        $controller = new CartController($this->ctpClient->reveal(), $this->cartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->deleteLineItemAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testAddShoppingListToCartAction()
    {
        $session = $this->prophesize(Session::class);
        $session->get('cart.id')->willReturn('cart-id-1')->shouldBeCalledOnce();
        $session->getId()->willReturn('session-1')->shouldBeCalledOnce();

        $user = $this->prophesize(CtpUser::class);

        $cart = Cart::of();

        $cartUpdateBuilder = $this->prophesize(CartUpdateBuilder::class);
        $cartUpdateBuilder->addShoppingList(Argument::type(CartAddShoppingListAction::class))->will(function () {
            return $this;
        })->shouldBeCalled();
        $cartUpdateBuilder->flush()->willReturn($cart)->shouldBeCalled();

        $this->cartManager->getCart('en', 'cart-id-1', $user->reveal(), 'session-1')->willReturn(Cart::of())->shouldBeCalledOnce();
        $this->cartManager->update(Argument::type(Cart::class))->willReturn($cartUpdateBuilder->reveal())->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate(Argument::type('string'), Argument::type('array'), 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->has('templating')->shouldNotBeCalled();
        $this->myContainer->has('twig')->shouldNotBeCalled();
        $this->myContainer->get('twig')->shouldNotBeCalled();

        $this->request->get('shoppingListId')->willReturn('foo')->shouldBeCalledOnce();

        $controller = new CartController($this->ctpClient->reveal(), $this->cartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->addShoppingListToCartAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testAddShoppingListToCartActionWithoutCart()
    {
        $session = $this->prophesize(Session::class);
        $session->get('cart.id')->willReturn(null)->shouldBeCalledOnce();

        $user = $this->prophesize(CtpUser::class);
        $user->getId()->willReturn('user-1')->shouldBeCalledOnce();

        $cart = Cart::of();

        $cartUpdateBuilder = $this->prophesize(CartUpdateBuilder::class);
        $cartUpdateBuilder->addShoppingList(Argument::type(CartAddShoppingListAction::class))->will(function () {
            return $this;
        })->shouldBeCalled();
        $cartUpdateBuilder->flush()->willReturn($cart)->shouldBeCalled();

        $this->cartManager->createCartForUser('en', 'EUR', Argument::type(Location::class), null, 'user-1')
            ->willReturn($cart)->shouldBeCalledOnce();
        $this->cartManager->update(Argument::type(Cart::class))->willReturn($cartUpdateBuilder->reveal())->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate(Argument::type('string'), Argument::type('array'), 1)->willReturn('bar')->shouldBeCalledOnce();

        $parameterBag = $this->prophesize(ParameterBag::class);
        $parameterBag->get('commercetools.project_settings.countries')->willReturn(['DE'])->shouldBeCalledOnce();
        $parameterBag->get('commercetools.project_settings.currencies')->willReturn(['EUR'])->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->has('templating')->shouldNotBeCalled();
        $this->myContainer->has('twig')->shouldNotBeCalled();
        $this->myContainer->get('twig')->shouldNotBeCalled();
        $this->myContainer->has('parameter_bag')->willReturn(true)->shouldBeCalledTimes(2);
        $this->myContainer->get('parameter_bag')->willReturn($parameterBag->reveal())->shouldBeCalledTimes(2);

        $this->request->get('shoppingListId')->willReturn('foo')->shouldBeCalledOnce();

        $controller = new CartController($this->ctpClient->reveal(), $this->cartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->addShoppingListToCartAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testAddShoppingListToCartActionWithoutCartForAnonymous()
    {
        $session = $this->prophesize(Session::class);
        $session->get('cart.id')->willReturn(null)->shouldBeCalledOnce();
        $session->getId()->willReturn('session-1')->shouldBeCalledOnce();

        $cart = Cart::of();

        $cartUpdateBuilder = $this->prophesize(CartUpdateBuilder::class);
        $cartUpdateBuilder->addShoppingList(Argument::type(CartAddShoppingListAction::class))->will(function () {
            return $this;
        })->shouldBeCalled();
        $cartUpdateBuilder->flush()->willReturn($cart)->shouldBeCalled();

        $this->cartManager->createCartForUser('en', 'EUR', Argument::type(Location::class), null, null, 'session-1')
            ->willReturn($cart)->shouldBeCalledOnce();
        $this->cartManager->update(Argument::type(Cart::class))->willReturn($cartUpdateBuilder->reveal())->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate(Argument::type('string'), Argument::type('array'), 1)->willReturn('bar')->shouldBeCalledOnce();

        $parameterBag = $this->prophesize(ParameterBag::class);
        $parameterBag->get('commercetools.project_settings.countries')->willReturn(['DE'])->shouldBeCalledOnce();
        $parameterBag->get('commercetools.project_settings.currencies')->willReturn(['EUR'])->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->has('templating')->shouldNotBeCalled();
        $this->myContainer->has('twig')->shouldNotBeCalled();
        $this->myContainer->get('twig')->shouldNotBeCalled();
        $this->myContainer->has('parameter_bag')->willReturn(true)->shouldBeCalledTimes(2);
        $this->myContainer->get('parameter_bag')->willReturn($parameterBag->reveal())->shouldBeCalledTimes(2);

        $this->request->get('shoppingListId')->willReturn('foo')->shouldBeCalledOnce();

        $controller = new CartController($this->ctpClient->reveal(), $this->cartManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->addShoppingListToCartAction($this->request->reveal(), $session->reveal());

        $this->assertTrue($response->isRedirect());
    }
}
