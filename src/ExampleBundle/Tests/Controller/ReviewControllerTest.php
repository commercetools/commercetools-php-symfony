<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Product\ProductReference;
use Commercetools\Core\Model\Review\Review;
use Commercetools\Core\Model\Review\ReviewCollection;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CustomerBundle\Security\User\User;
use Commercetools\Symfony\ExampleBundle\Controller\ReviewController;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddReviewType;
use Commercetools\Symfony\ReviewBundle\Manager\ReviewManager;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\Exception\InvalidArgumentException;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Workflow;
use Twig\Environment;

class ReviewControllerTest extends WebTestCase
{
    private $request;
    private $myContainer;
    private $twig;
    private $ctpClient;
    /** @var ReviewManager */
    private $reviewManager;
    private $registry;

    public function setUp()
    {
        $this->request = $this->prophesize(Request::class);
        $this->myContainer = $this->prophesize(ContainerInterface::class);
        $this->twig = $this->prophesize(Environment::class);
        $this->ctpClient = $this->prophesize(Client::class);
        $this->reviewManager = $this->prophesize(ReviewManager::class);
        $this->registry = $this->prophesize(Registry::class);

        $this->request->getLocale()->willReturn('en')->shouldBeCalledOnce();
    }

    public function testShowReviewsForProductAction()
    {
        $form = $this->prophesize(Form::class);
        $form->createView()->shouldBeCalledOnce();

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::is(AddReviewType::class), null, [])
            ->willReturn($form->reveal())->shouldBeCalled();

        $router = $this->prophesize(RouterInterface::class);
        $router->generate(Argument::is('_ctp_example_review_create'), ['productId' => 'product-1'], 1)->willReturn('')->shouldBeCalled();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalled();
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();
        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $this->reviewManager->getByProductId('en', 'product-1', Argument::type(QueryParams::class))
            ->willReturn('foo')->shouldBeCalledOnce();

        $controller = new ReviewController($this->ctpClient->reveal(), $this->reviewManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->showReviewsForProductAction($this->request->reveal(), 'product-1');

        $this->assertTrue($response->isOk());
    }

    public function testCreateReviewForProductAction()
    {
        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('user-1')->shouldBeCalledOnce();

        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function () {
                return $this;
            })->shouldBeCalled();
        $form->isSubmitted()->willReturn(true)->shouldBeCalledOnce();
        $form->isValid()->willReturn(true)->shouldBeCalledOnce();
        $form->get('text')->will(function () {
            return $this;
        })->shouldBeCalledOnce();
        $form->get('rating')->will(function () {
            return $this;
        })->shouldBeCalledOnce();
        $form->getData()->willReturn('foobar')->shouldBeCalledTimes(2);

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::is(AddReviewType::class), null, [])
            ->willReturn($form->reveal())->shouldBeCalled();

        $router = $this->prophesize(RouterInterface::class);
        $router->generate(Argument::is('_ctp_example_review_show'), ['productId' => 'product-1'], 1)->willReturn('')->shouldBeCalled();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalled();
        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $this->reviewManager->createForProduct(
            'en',
            Argument::type(ProductReference::class),
            Argument::type(CustomerReference::class),
            Argument::type('string'),
            Argument::type('string')
        )
            ->willReturn('foo')->shouldBeCalledOnce();

        $controller = new ReviewController($this->ctpClient->reveal(), $this->reviewManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->createReviewForProductAction($this->request->reveal(), 'product-1', $user->reveal());

        $this->assertTrue($response->isOk());
        $this->assertContains('"success":true', $response->getContent());
    }

    public function testCreateReviewForProductActionWithError()
    {
        $user = $this->prophesize(User::class);

        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function () {
                return $this;
            })->shouldBeCalled();
        $form->isSubmitted()->willReturn(false)->shouldBeCalledOnce();

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::is(AddReviewType::class), null, [])
            ->willReturn($form->reveal())->shouldBeCalled();

        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $this->request->getLocale()->willReturn('en')->shouldNotBeCalled();

        $controller = new ReviewController($this->ctpClient->reveal(), $this->reviewManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->createReviewForProductAction($this->request->reveal(), 'product-1', $user->reveal());

        $this->assertTrue($response->isOk());
        $this->assertContains('"success":false', $response->getContent());
    }

    public function testUpdateReviewActionWithoutUser()
    {
        $session = $this->prophesize(Session::class);

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('Do not allow anonymous reviews for now'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->request->getLocale()->willReturn('en')->shouldNotBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $controller = new ReviewController($this->ctpClient->reveal(), $this->reviewManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updateReviewAction($this->request->reveal(), 'review-1');

        $this->assertTrue($response->isOk());
    }

    public function testUpdateReviewAction()
    {
        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('user-1')->shouldBeCalledOnce();

        $this->request->get('toState')->willReturn('to-foo')->shouldBeCalledTimes(2);

        $router = $this->prophesize(RouterInterface::class);
        $router->generate(Argument::is('_ctp_example_product_by_id'), ['id' => 'product-1'], 1)->willReturn('foo')->shouldBeCalled();

        $this->myContainer->get('router')->willReturn($router)->shouldBeCalled();

        $reviewCollection = ReviewCollection::of()->add(
            Review::of()->setTarget(ProductReference::ofId('product-1'))
        );

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(Review::class), 'to-foo')->willReturn(true)->shouldBeCalledOnce();
        $workflow->apply(Argument::type(Review::class), 'to-foo')->willReturn(true)->shouldBeCalledOnce();


        $this->registry->get(Argument::type(Review::class))->willReturn($workflow)->shouldBeCalledOnce();

        $this->reviewManager->getReviewForUser('en', 'user-1', 'review-1')
            ->willReturn($reviewCollection)->shouldBeCalledOnce();

        $controller = new ReviewController($this->ctpClient->reveal(), $this->reviewManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updateReviewAction($this->request->reveal(), 'review-1', $user->reveal());

        $this->assertTrue($response->isRedirect());
    }

    public function testUpdateReviewActionCannotFindReview()
    {
        $session = $this->prophesize(Session::class);

        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('user-1')->shouldBeCalledOnce();

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('Cannot find review or not required permissions'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $this->reviewManager->getReviewForUser('en', 'user-1', 'review-1')
            ->willReturn(ReviewCollection::of())->shouldBeCalledOnce();

        $controller = new ReviewController($this->ctpClient->reveal(), $this->reviewManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updateReviewAction($this->request->reveal(), 'review-1', $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testUpdateReviewActionCannotFindWorkflow()
    {
        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('user-1')->shouldBeCalledOnce();

        $session = $this->prophesize(Session::class);

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('Cannot find proper workflow configuration. Action aborted'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $reviewCollection = ReviewCollection::of()->add(
            Review::of()->setTarget(ProductReference::ofId('product-1'))
        );

        $this->registry->get(Argument::type(Review::class))->will(function () {
            throw new InvalidArgumentException();
        })->shouldBeCalledOnce();

        $this->reviewManager->getReviewForUser('en', 'user-1', 'review-1')
            ->willReturn($reviewCollection)->shouldBeCalledOnce();

        $controller = new ReviewController($this->ctpClient->reveal(), $this->reviewManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updateReviewAction($this->request->reveal(), 'review-1', $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testUpdateReviewActionCannotTransition()
    {
        $user = $this->prophesize(User::class);
        $user->getId()->willReturn('user-1')->shouldBeCalledOnce();

        $session = $this->prophesize(Session::class);

        $this->request->get('toState')->willReturn('to-foo')->shouldBeCalledTimes(1);

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('Cannot perform this action'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();
        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();

        $reviewCollection = ReviewCollection::of()->add(
            Review::of()->setTarget(ProductReference::ofId('product-1'))
        );

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(Review::class), 'to-foo')->willReturn(false)->shouldBeCalledOnce();
        $workflow->apply(Argument::type(Review::class), 'to-foo')->shouldNotBeCalled();


        $this->registry->get(Argument::type(Review::class))->willReturn($workflow)->shouldBeCalledOnce();

        $this->reviewManager->getReviewForUser('en', 'user-1', 'review-1')
            ->willReturn($reviewCollection)->shouldBeCalledOnce();

        $controller = new ReviewController($this->ctpClient->reveal(), $this->reviewManager->reveal(), $this->registry->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->updateReviewAction($this->request->reveal(), 'review-1', $user->reveal());

        $this->assertTrue($response->isOk());
    }
}
