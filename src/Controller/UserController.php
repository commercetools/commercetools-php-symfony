<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Request\Customers\CustomerByIdGetRequest;
use Commercetools\Symfony\CtpBundle\Entity\CartEntity;
use Commercetools\Symfony\CtpBundle\Entity\UserAddress;
use Commercetools\Symfony\CtpBundle\Model\Form\Type\AddressType;
use Commercetools\Symfony\CtpBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CtpBundle\Security\User\User;
use Commercetools\Symfony\CtpBundle\Tests\Entity\UserAddressTest;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\DateTime;

class UserController extends Controller
{
    public function indexAction()
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        return $this->render('CtpBundle:catalog:index.html.twig',
            [
                'user' => $user
            ]
        );
    }

    public function loginAction(Request $request)
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('CtpBundle:user:login.html.twig',
            [
                'last_username' => $lastUsername,
                'error' => $error
            ]
        );
    }

    public function loginCheckAction(Request $request)
    {
        
    }

    public function detailsAction(Request $request)
    {
        /**
         * @var User $user
         */
        $customerId = $this->get('security.token_storage')->getToken()->getUser()->getId();
        $customer = $this->get('commercetools.repository.customer')->getCustomer($request->getLocale(), $customerId);

        $form = $this->createFormBuilder()
            ->add('firstName', TextType::class, array('required' => false, 'label' => 'First Name'))
            ->add('lastName', TextType::class, array('required' => false, 'label' => 'Last Name'))
            ->add('email', EmailType::class, array('required' => false, 'label' => 'Email'))
            ->add('currentPassword', PasswordType::class, array('required' => false, 'label' => 'Current Password'))
            ->add('newPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'first_options'  => array('label' => 'New password'),
                'second_options' => array('label' => 'Repeat new password'),
                'required' => false
            ))
            ->add('submit', SubmitType::class, array('label' => 'Save user'))
            ->getForm();

        $form->get('firstName')->setData($customer->getFirstName());
        $form->get('lastName')->setData($customer->getLastName());
        $form->get('email')->setData($customer->getEmail());

        $form->handleRequest($request);

        $userDetails = $form->getData();
        if ($form->isValid() && $form->isSubmitted()){

            $firstName = $form->getData()['firstName'];
            $lastName = $form->getData()['lastName'];
            $email = $form->getData()['email'];
            $currentPassword = $form->getData()['currentPassword'];
            $newPassword = $form->getData()['newPassword'];

            $customer = $this->get('commercetools.repository.customer')
                ->setCustomerDetails($request->getLocale(), $customer, $firstName, $lastName, $email);

            if (is_null($customer)){
                $this->addFlash('error', 'Error updating user!');
                return $this->redirect($this->generateUrl('_ctp_example_user_details'));
            }else{
                $this->addFlash('notice', 'User updated');
            }

            if (isset($newPassword)){
                try{
                    $this->get('commercetools.repository.customer')
                        ->setNewPassword($request->getLocale(), $customer, $currentPassword, $newPassword);
                } catch (\InvalidArgumentException $e){
                    $this->addFlash('error', $this->get($e->getMessage(), [] , 'customers'));
                    var_dump($e->getMessage());
                    return new Response($e->getMessage());
                }
            }

        }

        return $this->render('CtpBundle:User:user.html.twig',
            [
                'formDetails' => $form->createView()
            ]
        );
    }

    public function addressBookAction(Request $request)
    {
        /**
         * @var User $user
         */
        $customerId = $this->get('security.token_storage')->getToken()->getUser()->getId();
        $customer = $this->get('commercetools.repository.customer')->getCustomer($request->getLocale(), $customerId);

        return $this->render('CtpBundle:User:addressBook.html.twig',
            [
                'customer' => $customer
            ]
        );
    }

    public function editAddressAction(Request $request, $addressId)
    {
        /**
         * @var User $user
         */
        $customerId = $this->get('security.token_storage')->getToken()->getUser()->getId();
        $repository = $this->get('commercetools.repository.customer');
        $customer = $repository->getCustomer($request->getLocale(), $customerId);

        $address = $customer->getAddresses()->getById($addressId);

        $session = $this->get('session');
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->get('commercetools.repository.cart')->getCart($request->getLocale(), $cartId, $customerId);

        $userAddress = UserAddress::ofAddress($address);
        $entity = new UserAddress();

        $form = $this->createFormBuilder($entity)
            ->add('address', AddressType::class)
            ->add('Submit', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);

        $form->get('address')->get('firstName')->setData($userAddress->getFirstName());
        $form->get('address')->get('salutation')->setData($address->getSalutation());
        $form->get('address')->get('title')->setData($address->getTitle());
        $form->get('address')->get('firstName')->setData($address->getFirstName());
        $form->get('address')->get('lastName')->setData($address->getLastName());
        $form->get('address')->get('email')->setData($address->getEmail());
        $form->get('address')->get('streetName')->setData($address->getStreetName());
        $form->get('address')->get('streetNumber')->setData($address->getStreetNumber());
        $form->get('address')->get('building')->setData($address->getBuilding());
        $form->get('address')->get('apartment')->setData($address->getApartment());
        $form->get('address')->get('department')->setData($address->getDepartment());
        $form->get('address')->get('city')->setData($address->getCity());
        $form->get('address')->get('country')->setData($address->getCountry());
        $form->get('address')->get('region')->setData($address->getRegion());
        $form->get('address')->get('pOBox')->setData($address->getPOBox());
        $form->get('address')->get('additionalAddressInfo')->setData($address->getAdditionalAddressInfo());
        $form->get('address')->get('additionalStreetInfo')->setData($address->getAdditionalStreetInfo());
        $form->get('address')->get('phone')->setData($address->getPhone());
        $form->get('address')->get('mobile')->setData($address->getMobile());

//        $form = $this->createFormBuilder($userAddress)
//            ->add('title', TextType::class)
//            ->add('salutation', ChoiceType::class, [
//                'choices' => [
//                    'Mr' => 'mr',
//                    'Mrs' => 'mrs'
//                ]
//            ])
//            ->add('firstName', TextType::class)
//            ->add('lastName', TextType::class)
//            ->add('email', TextType::class)
//            ->add('company', TextType::class)
//            ->add('streetName', TextType::class)
//            ->add('streetNumber', TextType::class)9
//            ->add('building', TextType::class)
//            ->add('apartment', TextType::class)
//            ->add('department', TextType::class)
//            ->add('postalCode', TextType::class)
//            ->add('city', TextType::class)
//            ->add('country', TextType::class)
//            ->add('region', TextType::class)
//            ->add('state', TextType::class,
//                [
//                    'required' => false
//                ])
//            ->add('pOBox', TextType::class, ['label' => 'Postal Code'])
//            ->add('additionalAddressInfo', TextareaType::class,
//                [
//                    'attr'  => ['class' => 'form_text']
//                ])
//            ->add('additionalStreetInfo', TextareaType::class,
//                [
//                    'attr'  => ['class' => 'form_text']
//                ])
//            ->add('phone', TextType::class)
//            ->add('mobile', TextType::class)
//            ->add('Change', SubmitType::class)
//            ->getForm();

        if ($form->isValid() && $form->isSubmitted()){
            $repository->setAddresses($request->getLocale(), $customer, $address, $addressId);

            $submit = $repository->setAddresses(
                $request->getLocale(),
                $cartId,
                $address,
                $customerId
            );
        }

        return $this->render(
            'CtpBundle:User:editAddress.html.twig',
            [
                'form_address' => $form->createView()
            ]
        );
    }

    public function showOrdersAction(Request $request)
    {
        $orders = $this->get('commercetools.repository.order')->getOrders($request->getLocale(), $this->getUser()->getId());

        return $this->render('CtpBundle:user:orders.html.twig', [
            'orders' => $orders
        ]);
    }

    public function showOrderAction(Request $request, $orderId)
    {
        $order = $this->get('commercetools.repository.order')->getOrder($request->getLocale(), $orderId);

        return $this->render('CtpBundle:user:order.html.twig', [
            'order' => $order
         ]);
    }

    protected function getCustomer(User $user)
    {
        if (!$user instanceof User){
            throw new \InvalidArgumentException;
        }

        /**
         * @var Client $client
         */
        $client = $this->get('commercetools.client');

        $request = CustomerByIdGetRequest::ofId($user->getId());
        $response = $request->executeWithClient($client);

        $customer = $request->mapResponse($response);

        return $customer;
    }
}
