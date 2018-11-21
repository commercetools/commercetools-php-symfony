<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Common\AddressCollection;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\Customers\Command\CustomerChangeAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerChangeEmailAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetFirstNameAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetLastNameAction;
use Commercetools\Symfony\CustomerBundle\Manager\CustomerManager;
use Commercetools\Symfony\CustomerBundle\Model\CustomerUpdateBuilder;
use Commercetools\Symfony\CustomerBundle\Security\User\CtpUser;
use Commercetools\Symfony\ExampleBundle\Controller\UserController;
use Commercetools\Symfony\ExampleBundle\Entity\UserDetails;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserControllerTest extends WebTestCase
{
    private $request;
    private $myContainer;
    private $twig;
    private $client;
    /** @var CustomerManager */
    private $customerManager;

    public function setUp()
    {
        $this->request = $this->prophesize(Request::class);
        $this->myContainer = $this->prophesize(ContainerInterface::class);
        $this->twig = $this->prophesize(\Twig_Environment::class);
        $this->client = $this->prophesize(Client::class);
        $this->customerManager = $this->prophesize(CustomerManager::class);

        $this->request->getLocale()->willReturn('en')->shouldBeCalledOnce();

        $this->myContainer->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $this->myContainer->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $this->myContainer->get('twig')->willReturn($this->twig)->shouldBeCalledOnce();
    }

    public function testLoginAction()
    {
        $this->request->getLocale()->shouldNotBeCalled();

        $authenticationUtils = $this->prophesize(AuthenticationUtils::class);
        $authenticationUtils->getLastAuthenticationError()->willReturn('foo')->shouldBeCalledOnce();
        $authenticationUtils->getLastUsername()->willReturn('bar')->shouldBeCalledOnce();

        $this->myContainer->get('security.authentication_utils')->willReturn($authenticationUtils->reveal())->shouldBeCalled();

        $controller = new UserController($this->client->reveal(), $this->customerManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->loginAction($this->request->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testDetailsAction()
    {
        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function(){return $this;})->shouldBeCalled();
        $form->createView()->shouldBeCalled();
        $form->add(Argument::type('string'), Argument::type('string'))
            ->will(function(){return $this;})->shouldBeCalled();
        $form->isSubmitted()->willReturn(true)->shouldBeCalledOnce();
        $form->isValid()->willReturn(true)->shouldBeCalledOnce();
        $form->get(Argument::type('string'))->will(function(){return $this;})->shouldBeCalledTimes(5);
        $form->getData()->willReturn('foo')->shouldBeCalledTimes(5);
        $form->createView()->shouldBeCalled();

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::type('string'), Argument::type(UserDetails::class), Argument::type('array'))
            ->willReturn($form->reveal())->shouldBeCalled();

        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $customer = Customer::of()->setId('id-1');

        $customerUpdateBuilder = $this->prophesize(CustomerUpdateBuilder::class);
        $customerUpdateBuilder->setFirstName(Argument::type(CustomerSetFirstNameAction::class))->will(function(){return $this;})->shouldBeCalled();
        $customerUpdateBuilder->setLastName(Argument::type(CustomerSetLastNameAction::class))->will(function(){return $this;})->shouldBeCalled();
        $customerUpdateBuilder->changeEmail(Argument::type(CustomerChangeEmailAction::class))->will(function(){return $this;})->shouldBeCalled();
        $customerUpdateBuilder->flush()->willReturn($customer)->shouldBeCalled();

        $this->customerManager->getById('en', 'id-1')->willReturn($customer)->shouldBeCalled();
        $this->customerManager->update(Argument::type(Customer::class))->willReturn($customerUpdateBuilder->reveal())->shouldBeCalled();
        $this->customerManager->changePassword(Argument::type(Customer::class), 'foo', 'foo')->shouldBeCalled();

        $user = $this->prophesize(CtpUser::class);
        $user->getId()->willReturn('id-1')->shouldBeCalledOnce();

        $controller = new UserController($this->client->reveal(), $this->customerManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->detailsAction($this->request->reveal(), $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testDetailsActionWithError()
    {
        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function(){return $this;})->shouldBeCalled();
        $form->createView()->shouldBeCalled();
        $form->add(Argument::type('string'), Argument::type('string'))
            ->will(function(){return $this;})->shouldBeCalled();
        $form->isSubmitted()->willReturn(true)->shouldBeCalledOnce();
        $form->isValid()->willReturn(true)->shouldBeCalledOnce();
        $form->get(Argument::type('string'))->will(function(){return $this;})->shouldBeCalledTimes(5);
        $form->getData()->willReturn('foo')->shouldBeCalledTimes(5);
        $form->createView()->shouldBeCalled();

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::type('string'), Argument::type('string'))->shouldBeCalled();
        $session = $this->prophesize(Session::class);
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->create(Argument::type('string'), Argument::type(UserDetails::class), Argument::type('array'))
            ->willReturn($form->reveal())->shouldBeCalled();

        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();
        $this->myContainer->get('session')->willReturn($session->reveal())->shouldBeCalled();
        $this->myContainer->has('session')->willReturn(true)->shouldBeCalled();

        $customer = Customer::of()->setId('id-1');

        $customerUpdateBuilder = $this->prophesize(CustomerUpdateBuilder::class);
        $customerUpdateBuilder->setFirstName(Argument::type(CustomerSetFirstNameAction::class))->will(function(){return $this;})->shouldBeCalled();
        $customerUpdateBuilder->setLastName(Argument::type(CustomerSetLastNameAction::class))->will(function(){return $this;})->shouldBeCalled();
        $customerUpdateBuilder->changeEmail(Argument::type(CustomerChangeEmailAction::class))->will(function(){return $this;})->shouldBeCalled();
        $customerUpdateBuilder->flush()->will(function(){throw new \Exception();})->shouldBeCalled();

        $this->customerManager->getById('en', 'id-1')->willReturn($customer)->shouldBeCalled();
        $this->customerManager->update(Argument::type(Customer::class))->willReturn($customerUpdateBuilder->reveal())->shouldBeCalled();
        $this->customerManager->changePassword(Argument::type(Customer::class), 'foo', 'foo')->shouldBeCalled();

        $user = $this->prophesize(CtpUser::class);
        $user->getId()->willReturn('id-1')->shouldBeCalledOnce();

        $controller = new UserController($this->client->reveal(), $this->customerManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $response = $controller->detailsAction($this->request->reveal(), $user->reveal());

        $this->assertTrue($response->isOk());
    }

    public function testAddressBookAction()
    {
        $this->customerManager->getById('en', 'id-1')->shouldBeCalled();

        $user = $this->prophesize(CtpUser::class);
        $user->getId()->willReturn('id-1')->shouldBeCalledOnce();

        $controller = new UserController($this->client->reveal(), $this->customerManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $res = $controller->addressBookAction($this->request->reveal(), $user->reveal());

        $this->assertTrue($res->isOk());
    }

    public function testEditAddressAction()
    {
        $customer = Customer::of()
            ->setId('id-1')
            ->setAddresses(AddressCollection::of()
                ->add(Address::of()->setCountry('foo')->setId('bar'))
            );

        $customerUpdateBuilder = $this->prophesize(CustomerUpdateBuilder::class);
        $customerUpdateBuilder->changeAddress(Argument::type(CustomerChangeAddressAction::class))->will(function(){return $this;})->shouldBeCalled();
        $customerUpdateBuilder->flush()->willReturn($customer)->shouldBeCalled();

        $this->customerManager->getById('en', 'id-1')->willReturn($customer)->shouldBeCalled();
        $this->customerManager->update(Argument::type(Customer::class))->willReturn($customerUpdateBuilder->reveal())->shouldBeCalled();

        $form = $this->prophesize(Form::class);
        $form->handleRequest(Argument::type(Request::class))
            ->will(function(){return $this;})->shouldBeCalled();
        $form->createView()->shouldBeCalled();
        $form->isSubmitted()->willReturn(true)->shouldBeCalledOnce();
        $form->isValid()->willReturn(true)->shouldBeCalledOnce();

        $form->get(Argument::is('address'))->will(function(){return $this;})->shouldBeCalledTimes(1);
        $form->getData()->willReturn([])->shouldBeCalledTimes(1);

        $form->createView()->shouldBeCalled();

        $formBuilder = $this->prophesize(FormBuilder::class);
        $formBuilder->add(Argument::type('string'), Argument::type('string'))
            ->will(function(){return $this;})->shouldBeCalledTimes(2);
        $formBuilder->getForm()->willReturn($form)->shouldBeCalled();

        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory->createBuilder(Argument::is(FormType::class), Argument::type('array'), [])
            ->willReturn($formBuilder->reveal())->shouldBeCalled();

        $this->myContainer->get('form.factory')->willReturn($formFactory->reveal())->shouldBeCalled();

        $user = $this->prophesize(CtpUser::class);
        $user->getId()->willReturn('id-1')->shouldBeCalledOnce();

        $controller = new UserController($this->client->reveal(), $this->customerManager->reveal());
        $controller->setContainer($this->myContainer->reveal());
        $res = $controller->editAddressAction($this->request->reveal(), $user->reveal(), 'bar');

        $this->assertTrue($res->isOk());
    }
}
