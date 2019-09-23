<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Request\Customers\Command\CustomerAddAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerChangeAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerChangeEmailAction;
use Commercetools\Core\Request\Customers\Command\CustomerRemoveAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetDefaultBillingAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetDefaultShippingAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetFirstNameAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetLastNameAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetTitleAction;
use Commercetools\Symfony\CustomerBundle\Manager\MeCustomerManager;
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
     * @var CustomerManager
     */
    private $manager;

    /** @var MeCustomerManager */
    private $meCustomerManager;

    /**
     * CustomerController constructor.
     * @param CustomerManager $manager
     * @param MeCustomerManager $meCustomerManager
     */
    public function __construct(CustomerManager $manager, MeCustomerManager $meCustomerManager)
    {
        $this->manager = $manager;
        $this->meCustomerManager = $meCustomerManager;
    }

    public function addAddressAction(Request $request)
    {
        $customer = $this->meCustomerManager->getMeInfo($request->getLocale());

        $form = $this->createForm(AddressType::class, new UserAddress())
            ->add('submit', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UserAddress $userAddress */
            $userAddress = $form->getData();
            $address = Address::fromArray($userAddress->toArray());

            $customerBuilder = $this->manager->update($customer)
                ->addAddress(CustomerAddAddressAction::ofAddress($address));
            $customerResponse = $customerBuilder->flush();

            $addresses = $customerResponse->getAddresses()->toArray();

            if (isset(end($addresses)['id'])) {
                $addressId = end($addresses)['id'];
                return $this->redirect($this->generateUrl('_ctp_example_user_address_edit', ['addressId' => $addressId]));
            }
        }

        return $this->render('@Example/my-account-new-address.html.twig', [
            'formAddress' => $form->createView(),
            'customer' => $customer
        ]);
    }

    public function loginAction(Request $request, AuthenticationUtils $authenticationUtils)
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@Example/my-account-login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    public function detailsAction(Request $request)
    {
        $customer = $this->meCustomerManager->getMeInfo($request->getLocale());
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

            $customerBuilder = $this->meCustomerManager->update($customer);
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

    public function deleteAddressAction(Request $request, UserInterface $user, $addressId)
    {
        $customer = $this->manager->getById($request->getLocale(), $user->getId());

        $customerBuilder = $this->manager->update($customer)
            ->removeAddress(CustomerRemoveAddressAction::ofAddressId($addressId));
        $customer = $customerBuilder->flush();

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

            if ($userAddress->getIsDefaultBillingAddress()) {
                $customerBuilder->addAction(CustomerSetDefaultBillingAddressAction::of()->setAddressId($addressId));
            }

            if ($userAddress->getIsDefaultShippingAddress()) {
                $customerBuilder->addAction(CustomerSetDefaultShippingAddressAction::of()->setAddressId($addressId));
            }

            $customerBuilder->flush();
        }

        return $this->render('@Example/my-account-edit-address.html.twig', [
            'formAddress' => $form->createView(),
            'addressId' => $addressId
        ]);
    }

    public function signUpAction()
    {
    }
}
