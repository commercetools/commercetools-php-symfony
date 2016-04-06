<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\Customers\CustomerByIdGetRequest;
use Commercetools\Core\Request\Customers\CustomerPasswordChangeRequest;
use Commercetools\Symfony\CtpBundle\Entity\UserAddress;
use Commercetools\Symfony\CtpBundle\Entity\UserDetails;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Commercetools\Symfony\CtpBundle\Entity\User;

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

    public function loginCheckAction()
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
            ->add('email', TextType::class, array('required' => false, 'label' => 'Email'))
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

            if (isset($currentPassword)){
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

        $entity = UserAddress::ofAddress($address);

        $form = $this->createFormBuilder($entity)
            ->add('title', TextType::class)
            ->add('salutation', ChoiceType::class, [
                'choices' => [
                    'Mr' => 'mr',
                    'Mrs' => 'mrs'
                ]
            ])
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('email', TextType::class)
            ->add('company', TextType::class)
            ->add('streetName', TextType::class)
            ->add('streetNumber', TextType::class)
            ->add('building', TextType::class)
            ->add('apartment', TextType::class)
            ->add('department', TextType::class)
            ->add('postalCode', TextType::class)
            ->add('city', TextType::class)
            ->add('country', TextType::class)
            ->add('region', TextType::class)
            ->add('state', TextType::class)
            ->add('pOBox', TextType::class, ['label' => 'Postal Code'])
            ->add('additionalAddressInfo', TextType::class)
            ->add('additionalStreetInfo', TextType::class)
            ->add('phone', TextType::class)
            ->add('mobile', TextType::class)
            ->add('Change', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()){
            $repository->setAddresses($request->getLocale(), $customer, $form->getData()->toCTPAddress(), $addressId);
        }

        return $this->render(
            'CtpBundle:User:editAddress.html.twig',
            [
                'form' => $form->createView()
            ]
        );
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