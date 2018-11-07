<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Controller;


use Commercetools\Core\Client;
use Commercetools\Core\Helper\CurrencyFormatter;
use Commercetools\Core\Model\Common\Context;
use Commercetools\Core\Model\Common\Image;
use Commercetools\Core\Model\Common\ImageCollection;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Common\Price;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\Product\ProductProjectionCollection;
use Commercetools\Core\Model\Product\ProductVariant;
use Commercetools\Core\Model\Product\ProductVariantCollection;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CustomerBundle\Security\User\CtpUser;
use Commercetools\Symfony\ExampleBundle\Controller\CatalogController;
use Commercetools\Symfony\ExampleBundle\Entity\ProductEntity;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

class CatalogControllerTest extends WebTestCase
{
    private $request;
    private $myContainer;
    private $twig;
    private $client;
    /** @var CatalogManager */
    private $catalogManager;

    public function setUp()
    {
        $this->request = $this->prophesize(Request::class);
        $this->myContainer = $this->prophesize(ContainerInterface::class);
        $this->twig = $this->prophesize(\Twig_Environment::class);
        $this->client = $this->prophesize(Client::class);
        $this->catalogManager = $this->prophesize(CatalogManager::class);

        $this->request->getLocale()->willReturn('en')->shouldBeCalledOnce();

        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();
    }

    public function testIndexAction()
    {
        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function(){return $this;})->shouldBeCalled();
        $form->isSubmitted()->willReturn(true)->shouldBeCalledOnce();
        $form->isValid()->willReturn(true)->shouldBeCalledOnce();
        $form->get('search')->will(function(){return $this;})->shouldBeCalledOnce();
        $form->getData()->willReturn(true)->shouldBeCalledOnce();
        $form->createView()->shouldBeCalled();

        $formBuilder = $this->prophesize(FormBuilder::class);
        $formBuilder->add(Argument::type('string'), Argument::type('string'), Argument::type('array'))
            ->will(function(){return $this;})->shouldBeCalledTimes(2);
        $formBuilder->getForm()->willReturn($form)->shouldBeCalled();

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->createBuilder(Argument::is(FormType::class), null, [])
            ->willReturn($formBuilder->reveal())->shouldBeCalled();

        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $this->request->getRequestUri()->shouldBeCalled();

        $this->myContainer->getParameter(Argument::type('string'))->willReturn(['foo'], ['bar'])->shouldBeCalled();

        $controller = new CatalogController($this->client->reveal(), $this->catalogManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->indexAction($this->request->reveal(), 'category', 'type');

        $this->assertTrue($response->isOk());
    }

    public function testDetailBySlugAction()
    {
        $session = $this->prophesize(SessionInterface::class);
        $shoppingListManager = $this->prophesize(ShoppingListManager::class);
        $shoppingListManager->getAllOfAnonymous('en', null)
            ->willReturn([])->shouldBeCalled();

        $this->request->getLocale()->willReturn('en')->shouldBeCalled();

        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function(){return $this;})->shouldBeCalled();
        $form->createView()->shouldBeCalled();

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::type('string'), Argument::type(ProductEntity::class), Argument::type('array'))
            ->willReturn($form->reveal())->shouldBeCalled();

        $router = $this->prophesize(RouterInterface::class);
        $router->generate(Argument::type('string'), [], 1)->willReturn('')->shouldBeCalled();

        $this->myContainer->getParameter(Argument::type('string'))->willReturn(['bar'], ['foo'])->shouldBeCalled();
        $this->myContainer->get('router')->willReturn($router)->shouldBeCalled();
        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $this->markTestIncomplete();
        // TODO getAllVariants does need raw data

        $productProjection = ProductProjection::of()->setId('projection-1')
            ->setVariants(
                ProductVariantCollection::of()
                    ->add(ProductVariant::of()->setId(1)->setSku('prod-1'))
                    ->add(ProductVariant::of()->setId(2)->setSku('prod-2'))
                    ->add(ProductVariant::of()->setId(3)->setSku('prod-3'))
                )
            ->setMasterVariant(ProductVariant::of()->setId(3)->setSku('prod-3')->setKey('key-3'));

        $variantIds = [];

        foreach ($productProjection->getAllVariants() as $variant) {
            $variantIds[$variant->getSku()] = $variant->getId();
        }

        $this->catalogManager->getProductBySlug('en', 'prod-1', 'foo', 'bar')->willReturn($productProjection)->shouldBeCalledOnce();

        $controller = new CatalogController($this->client->reveal(), $this->catalogManager->reveal(), $shoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $controller->detailBySlugAction($this->request->reveal(), 'prod-1', $session->reveal());
    }


    public function testDetailBySlugNotFound()
    {
        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::type('string'), Argument::type('string'))->shouldBeCalled();

        $session = $this->prophesize(Session::class);
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $shoppingListManager = $this->prophesize(ShoppingListManager::class);

        $this->request->getLocale()->willReturn('en')->shouldBeCalled();

        $this->myContainer->getParameter(Argument::type('string'))->willReturn(['bar'], ['foo'])->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();
        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();


        $this->catalogManager->getProductBySlug('en', 'prod-1', 'foo', 'bar')
            ->will(function(){throw new NotFoundHttpException();})->shouldBeCalledOnce();

        $controller = new CatalogController($this->client->reveal(), $this->catalogManager->reveal(), $shoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->detailBySlugAction($this->request->reveal(), 'prod-1', $session->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testDetailByIdAction()
    {
        $session = $this->prophesize(SessionInterface::class);
        $shoppingListManager = $this->prophesize(ShoppingListManager::class);
        $shoppingListManager->getAllOfCustomer('en', Argument::type(CustomerReference::class))
            ->willReturn([])->shouldBeCalled();

        $user = $this->prophesize(CtpUser::class);
        $user->getId()->willReturn('user-1')->shouldBeCalled();

        $this->request->getLocale()->willReturn('en')->shouldBeCalled();

        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function(){return $this;})->shouldBeCalled();
        $form->createView()->shouldBeCalled();

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::type('string'), Argument::type(ProductEntity::class), Argument::type('array'))
            ->willReturn($form->reveal())->shouldBeCalled();

        $router = $this->prophesize(RouterInterface::class);
        $router->generate(Argument::type('string'), [], 1)->willReturn('')->shouldBeCalled();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalled();
        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $this->catalogManager->getProductById('en', 'prod-1')->willReturn(ProductProjection::of())->shouldBeCalledOnce();

        $controller = new CatalogController($this->client->reveal(), $this->catalogManager->reveal(), $shoppingListManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->detailByIdAction($this->request->reveal(), 'prod-1', $session->reveal(), $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testSuggestAction()
    {
        $this->myContainer->has('templating')->willReturn(false)->shouldNotBeCalled();
        $this->myContainer->has('twig')->willReturn(true)->shouldNotBeCalled();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldNotBeCalled();

        $this->myContainer->getParameter(Argument::type('string'))->willReturn(['bar'], ['baz'])->shouldBeCalled();

        // TODO ~context related~
        $context = new Context();
        $context->setLanguages([0 => 'en']);

        $productProjectionCollection = ProductProjectionCollection::of()
            ->setContext($context)
            ->add(ProductProjection::of()
                ->setKey('key-1')
                ->setId('id-1')
                ->setName(LocalizedString::ofLangAndText('en', 'name-1'))
                ->setDescription(LocalizedString::ofLangAndText('en', 'desc-1'))
                ->setSlug(LocalizedString::ofLangAndText('en', 'test-slug'))
                ->setMasterVariant(ProductVariant::of()
                    ->setId(1)
                    ->setImages(ImageCollection::of()->add(Image::of()->setUrl('foo://bar')))
                    ->setPrice(Price::ofMoney(Money::ofCurrencyAndAmount('EUR',100)))
                )
            );

        $this->catalogManager->suggestProducts('en', 'foo', 5, 'baz', 'bar')->willReturn($productProjectionCollection)->shouldBeCalledOnce();

        $controller = new CatalogController($this->client->reveal(), $this->catalogManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $suggest = $controller->suggestAction($this->request->reveal(), 'foo');

        $currencyFormatter = new CurrencyFormatter($context);

        $data = [
            'id-1' => [
                'link' => 'test-slug',
                'name' => 'name-1',
                'image' => 'foo://bar',
                'desc' => 'desc-1',
                'price' => $currencyFormatter->format(100, 'EUR')
            ]
        ];
        $expected = new JsonResponse($data);
        $this->assertEquals($expected, $suggest);
    }

    public function testGetProductTypesAction()
    {
        $this->catalogManager->getProductTypes('en', Argument::type(QueryParams::class))->willReturn('')->shouldBeCalledOnce();

        $this->twig->render('ExampleBundle:catalog:productTypesList.html.twig', ['productTypes' => ''])->shouldBeCalledOnce();

        $controller = new CatalogController($this->client->reveal(), $this->catalogManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->getProductTypesAction($this->request->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testGetCategoriesAction()
    {
        $this->catalogManager->getCategories('en', Argument::type(QueryParams::class))->willReturn('')->shouldBeCalledOnce();

        $this->twig->render('ExampleBundle:catalog:categoriesList.html.twig', ['categories' => ''])->shouldBeCalledOnce();

        $controller = new CatalogController($this->client->reveal(), $this->catalogManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->getCategoriesAction($this->request->reveal());

        $this->assertTrue($response->isOk());
    }
}
