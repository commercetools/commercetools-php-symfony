<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\Context;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Model\ShoppingList\ShoppingListCollection;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListAddLineItemAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeLineItemQuantityAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListRemoveLineItemAction;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CustomerBundle\Security\User\User;
use Commercetools\Symfony\ExampleBundle\Controller\ShoppingListController;
use Commercetools\Symfony\ExampleBundle\Entity\ProductToShoppingList;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToShoppingListType;
use Commercetools\Symfony\ShoppingListBundle\Manager\MeShoppingListManager;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;
use Commercetools\Symfony\ShoppingListBundle\Model\ShoppingListUpdateBuilder;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Twig\Environment;

class ShoppingListControllerTest extends WebTestCase
{
    private $request;
    private $myContainer;
    private $twig;
    /** @var ShoppingListManager */
    private $shoppingListManager;
    /** @var MeShoppingListManager */
    private $meShoppingListManager;

    public function setUp()
    {
        $this->request = $this->prophesize(Request::class);
        $this->myContainer = $this->prophesize(ContainerInterface::class);
        $this->twig = $this->prophesize(Environment::class);
        $this->shoppingListManager = $this->prophesize(ShoppingListManager::class);
        $this->meShoppingListManager = $this->prophesize(MeShoppingListManager::class);

        $this->request->getLocale()->willReturn('en')->shouldBeCalledOnce();
    }

    public function testIndexActionForAnonymous()
    {
        $this->markTestSkipped();

        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $this->shoppingListManager->getAllOfAnonymous('en', 'baz', Argument::type(QueryParams::class))
            ->willReturn('foo')->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->shoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->indexAction($this->request->reveal(), $session->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testIndexAction()
    {
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $this->meShoppingListManager->getAllMyShoppingLists('en', Argument::type(QueryParams::class))
            ->willReturn('foo')->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->meShoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->indexAction($this->request->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testIndexActionForCustomer()
    {
        $this->markTestSkipped();

        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('foo')->shouldBeCalledOnce();

        $session = $this->prophesize(Session::class);

        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $this->shoppingListManager->getAllOfCustomer('en', Argument::type(CustomerReference::class), Argument::type(QueryParams::class))
            ->willReturn('foo')->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->shoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->indexAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testCreateActionForAnonymous()
    {
        $this->markTestSkipped();

        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $this->request->get('shoppingListName')->willReturn('bar')->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_shoppingList', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $this->shoppingListManager->createShoppingListByAnonymous('en', 'baz', 'bar')
            ->willReturn('foo')->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->shoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->createAction($this->request->reveal(), $session->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testCreateActionForCustomer()
    {
        $this->markTestSkipped();

        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('foo')->shouldBeCalledOnce();

        $session = $this->prophesize(Session::class);

        $this->request->get('shoppingListName')->willReturn('bar')->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_shoppingList', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $this->shoppingListManager->createShoppingListByCustomer('en', Argument::type(CustomerReference::class), 'bar')
            ->willReturn('foo')->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->shoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->createAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testCreateAction()
    {
        $this->request->get('shoppingListName')->willReturn('bar')->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_shoppingList', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $this->meShoppingListManager->createShoppingList('en', 'bar')
            ->willReturn('foo')->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->meShoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->createAction($this->request->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testDeleteByIdActionForAnonymous()
    {
        $this->markTestSkipped();

        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_cart', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $this->shoppingListManager->getShoppingListForUser('en', 'bar', null, 'baz')
            ->willReturn(ShoppingList::of())->shouldBeCalledOnce();
        $this->shoppingListManager->deleteShoppingList('en', Argument::type(ShoppingList::class))
            ->willReturn('foo')->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->shoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->deleteByIdAction($this->request->reveal(), 'bar', $session->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testDeleteByIdAction()
    {
        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_cart', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $this->meShoppingListManager->getById('en', 'bar')
            ->willReturn(ShoppingList::of())->shouldBeCalledOnce();
        $this->meShoppingListManager->deleteShoppingList('en', Argument::type(ShoppingList::class))
            ->willReturn('foo')->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->meShoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->deleteByIdAction($this->request->reveal(), 'bar');

        $this->assertTrue($response->isRedirect());
    }

    public function testDeleteByActionForCustomer()
    {
        $this->markTestSkipped();

        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('foo')->shouldBeCalledOnce();

        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $session = $this->prophesize(Session::class);

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_cart', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $this->shoppingListManager->getShoppingListForUser('en', 'bar', Argument::type(CustomerReference::class), null)
            ->willReturn(ShoppingList::of())->shouldBeCalledOnce();
        $this->shoppingListManager->deleteShoppingList('en', Argument::type(ShoppingList::class))
            ->willReturn('foo')->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->shoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->deleteByIdAction($this->request->reveal(), 'bar', $session->reveal(), $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testAddLineItemActionForCustomer()
    {
        $this->markTestSkipped();

        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('foo')->shouldBeCalledOnce();

        $session = $this->prophesize(Session::class);

        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function () {
                return $this;
            })->shouldBeCalled();
        $form->isSubmitted()->willReturn(true)->shouldBeCalledOnce();
        $form->isValid()->willReturn(true)->shouldBeCalledOnce();
        $form->get('shoppingListId')->will(function () {
            $this->getData()->willReturn('list-1');
            return $this;
        });

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::is(AddToShoppingListType::class), Argument::type(ProductToShoppingList::class), Argument::type('array'))
            ->willReturn($form->reveal())->shouldBeCalled();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_shoppingList', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        // TODO ~context related~
        $context = new Context();
        $context->setLanguages([0 => 'en']);

        $shoppingListCollection = ShoppingListCollection::of()->add(
            ShoppingList::of()->setId('list-1')->setName(LocalizedString::ofLangAndText('en', 'list-name-1'))
        )->setContext($context);

        $shoppingListUpdateBuilder = $this->prophesize(ShoppingListUpdateBuilder::class);
        $shoppingListUpdateBuilder->addLineItem(Argument::type('closure'))->will(function () {
            return $this;
        })->shouldBeCalled();
        $shoppingListUpdateBuilder->flush()->shouldBeCalled();

        $this->shoppingListManager->getAllOfCustomer('en', Argument::type(CustomerReference::class))
            ->willReturn($shoppingListCollection)->shouldBeCalledOnce();
        $this->shoppingListManager->getById('en', 'list-1')
            ->willReturn(ShoppingList::of())->shouldBeCalledOnce();
        $this->shoppingListManager->update(Argument::type(ShoppingList::class))->willReturn($shoppingListUpdateBuilder)
            ->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->shoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->addLineItemAction($this->request->reveal(), $session->reveal(), $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testAddLineItemAction()
    {
        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function () {
                return $this;
            })->shouldBeCalled();
        $form->isSubmitted()->willReturn(true)->shouldBeCalledOnce();
        $form->isValid()->willReturn(true)->shouldBeCalledOnce();
        $form->get('shoppingListId')->will(function () {
            $this->getData()->willReturn('list-1');
            return $this;
        });

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::is(AddToShoppingListType::class), Argument::type(ProductToShoppingList::class), Argument::type('array'))
            ->willReturn($form->reveal())->shouldBeCalled();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_shoppingList', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $this->request->getLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $context = new Context();
        $context->setLanguages([0 => 'en']);

        $shoppingListCollection = ShoppingListCollection::of()->add(
            ShoppingList::of()->setId('list-1')->setName(LocalizedString::ofLangAndText('en', 'list-name-1'))
        )->setContext($context);

        $shoppingListUpdateBuilder = $this->prophesize(ShoppingListUpdateBuilder::class);
        $shoppingListUpdateBuilder->addLineItem(Argument::type('closure'))->will(function () {
            return $this;
        })->shouldBeCalled();
        $shoppingListUpdateBuilder->flush()->shouldBeCalled();

        $this->meShoppingListManager->getAllMyShoppingLists('en')
            ->willReturn($shoppingListCollection)->shouldBeCalledOnce();
        $this->meShoppingListManager->getById('en', 'list-1')
            ->willReturn(ShoppingList::of())->shouldBeCalledOnce();
        $this->meShoppingListManager->update(Argument::type(ShoppingList::class))->willReturn($shoppingListUpdateBuilder)
            ->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->meShoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->addLineItemAction($this->request->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testAddLineItemActionForAnonymousWithError()
    {
        $this->markTestSkipped();

        $session = $this->prophesize(Session::class);
        $session->getId()->willReturn('baz')->shouldBeCalledOnce();

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('Not valid shopping list provided'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function () {
                return $this;
            })->shouldBeCalled();
        $form->isSubmitted()->willReturn(true)->shouldBeCalledOnce();
        $form->isValid()->willReturn(true)->shouldBeCalledOnce();
        $form->get('shoppingListId')->will(function () {
            $this->getData()->willReturn(null);
            return $this;
        });

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::is(AddToShoppingListType::class), Argument::type(ProductToShoppingList::class), Argument::type('array'))
            ->willReturn($form->reveal())->shouldBeCalled();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_shoppingList', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();
        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();
        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();

        // TODO ~context related~
        $context = new Context();
        $context->setLanguages([0 => 'en']);

        $shoppingListCollection = ShoppingListCollection::of()->add(
            ShoppingList::of()->setId('list-1')->setName(LocalizedString::ofLangAndText('en', 'list-name-1'))
        )->setContext($context);

        $this->shoppingListManager->getAllOfAnonymous('en', 'baz')
            ->willReturn($shoppingListCollection)->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->shoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->addLineItemAction($this->request->reveal(), $session->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testRemoveLineItemAction()
    {
        $this->request->get('shoppingListId')->willReturn('bar')->shouldBeCalledOnce();
        $this->request->get('lineItemId')->willReturn('foobar')->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_shoppingList', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $shoppingListUpdateBuilder = $this->prophesize(ShoppingListUpdateBuilder::class);
        $shoppingListUpdateBuilder->addAction(Argument::type(ShoppingListRemoveLineItemAction::class))->will(function () {
            return $this;
        })->shouldBeCalled();
        $shoppingListUpdateBuilder->flush()->shouldBeCalled();

        $this->meShoppingListManager->getById('en', 'bar')
            ->willReturn(ShoppingList::of())->shouldBeCalledOnce();
        $this->meShoppingListManager->update(Argument::type(ShoppingList::class))->willReturn($shoppingListUpdateBuilder)
            ->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->meShoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->removeLineItemAction($this->request->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testChangeLineItemAction()
    {
        $this->request->get('shoppingListId')->willReturn('bar')->shouldBeCalledOnce();
        $this->request->get('lineItemId')->willReturn('foobar')->shouldBeCalledOnce();
        $this->request->get('lineItemQuantity')->willReturn('5')->shouldBeCalledOnce();

        $router = $this->prophesize(Router::class);
        $router->generate('_ctp_example_shoppingList', [], 1)->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalledOnce();

        $shoppingListUpdateBuilder = $this->prophesize(ShoppingListUpdateBuilder::class);
        $shoppingListUpdateBuilder->addAction(Argument::type(ShoppingListChangeLineItemQuantityAction::class))->will(function () {
            return $this;
        })->shouldBeCalled();
        $shoppingListUpdateBuilder->flush()->shouldBeCalled();

        $this->meShoppingListManager->getById('en', 'bar')
            ->willReturn(ShoppingList::of())->shouldBeCalledOnce();
        $this->meShoppingListManager->update(Argument::type(ShoppingList::class))->willReturn($shoppingListUpdateBuilder)
            ->shouldBeCalledOnce();

        $controller = new ShoppingListController($this->meShoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->changeLineItemQuantityAction($this->request->reveal());

        $this->assertTrue($response->isRedirect());
    }
}
