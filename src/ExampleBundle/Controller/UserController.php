<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Request\Customers\Command\CustomerChangeAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerChangeEmailAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetFirstNameAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetLastNameAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetTitleAction;
use Commercetools\Symfony\ExampleBundle\Entity\UserAddress;
use Commercetools\Symfony\ExampleBundle\Entity\UserDetails;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddressType;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\UserType;
use Commercetools\Symfony\CustomerBundle\Manager\CustomerManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var CustomerManager
     */
    private $manager;

    /**
     * CustomerController constructor.
     */
    public function __construct(Client $client, CustomerManager $manager)
    {
        $this->client = $client;
        $this->manager = $manager;
    }

//    public function indexAction()
//    {
//        /**
//         * @var User $user
//         */
//        $user = $this->getUser();
//
//        return $this->render('ExampleBundle:catalog:index.html.twig',
//            [
//                'user' => $user
//            ]
//        );
//    }

    public function loginAction(Request $request, AuthenticationUtils $authenticationUtils)
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@Example/my-account-login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    public function detailsAction(Request $request, UserInterface $user)
    {
        $customer = $this->manager->getById($request->getLocale(), $user->getId());
        $entity = UserDetails::ofCustomer($customer);

        $form = $this->createForm(UserType::class, $entity)
            ->add('submit', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $firstName = $form->get('firstName')->getData() ?? '';
            $lastName = $form->get('lastName')->getData() ?? '';
            $email = $form->get('email')->getData() ?? '';
            $title = $form->get('title')->getData() ?? '';

            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            $customerBuilder = $this->manager->update($customer);
            $customerBuilder
                ->setFirstName(CustomerSetFirstNameAction::of()->setFirstName($firstName))
                ->setLastName(CustomerSetLastNameAction::of()->setLastName($lastName))
                ->changeEmail(CustomerChangeEmailAction::ofEmail($email))
                ->setTitle(CustomerSetTitleAction::of()->setTitle($title));

            try {
                $customer = $customerBuilder->flush();
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

            if (isset($newPassword)) {
                $this->manager->changePassword($customer, $currentPassword, $newPassword);
            }
        }

        return $this->render('@Example/my-account-personal-details.html.twig', [
            'formDetails' => $form->createView()
        ]);
    }

    public function addressBookAction(Request $request, UserInterface $user)
    {
        $customer = $this->manager->getById($request->getLocale(), $user->getId());

        return $this->render('@Example/my-account-address-book.html.twig', [
            'customer' => $customer
        ]);
    }

    public function editAddressAction(Request $request, UserInterface $user, $addressId)
    {
        $customer = $this->manager->getById($request->getLocale(), $user->getId());
        $address = $customer->getAddresses()->getById($addressId);

        $entity = UserAddress::ofAddress($address);

        $form = $this->createForm(AddressType::class, $entity)
            ->add('submit', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UserAddress $userAddress */
            $userAddress = $form->getData();
            $address = Address::fromArray($userAddress->toArray());

            $customerBuilder = $this->manager->update($customer)
                ->changeAddress(CustomerChangeAddressAction::ofAddressIdAndAddress($addressId, $address));
            $customerBuilder->flush();
        }

        return $this->render('@Example/my-account-edit-address.html.twig', [
            'formAddress' => $form->createView(),
            'addressId' => $addressId
        ]);
    }

//    protected function getCustomer(User $user)
//    {
//        if (!$user instanceof User){
//            throw new \InvalidArgumentException;
//        }
//
//        /**
//         * @var Client $client
//         */
//        $client = $this->get('commercetools.client');
//
//        $request = CustomerByIdGetRequest::ofId($user->getId());
//        $response = $request->executeWithClient($client);
//
//        $customer = $request->mapResponse($response);
//
//        return $customer;
//    }

    public function signUpAction()
    {
    }
}
