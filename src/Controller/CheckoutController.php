<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Controller;

use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\ShippingMethod\ShippingMethod;
use Commercetools\Symfony\CtpBundle\Entity\CartEntity;
use Commercetools\Symfony\CtpBundle\Model\Form\Type\AddressType;
use Commercetools\Symfony\CtpBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CtpBundle\Security\User\CtpUser;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CheckoutController extends Controller
{
    public function signinAction(Request $request)
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirect($this->generateUrl('_ctp_example_checkout_address'));
        }

        $authenticationUtils = $this->get('security.authentication_utils');

        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('CtpBundle:checkout:secureCheckout.html.twig',
            [
                'last_username' => $lastUsername,
                'error' => $error
            ]
        );
    }

    public function shippingMethodAction(Request $request)
    {
        $shippingRepository = $this->get('commercetools.repository.shipping_method');

        $session = $this->get('session');
        $cartId = $session->get(CartRepository::CART_ID);
        $shippingMethods = $shippingRepository->getShippingMethodByCart($request->getLocale(), $cartId);

        $customerId = null;
        if ($this->get('security.token_storage')->getToken()->getUser() instanceof CtpUser) {
            $customerId = $this->get('security.token_storage')->getToken()->getUser()->getId();
        }
        $cart = $this->get('commercetools.repository.cart')->getCart($request->getLocale(), $cartId, $customerId);

        if (is_null($cart->getId())) {
            return $this->redirect($this->generateUrl('_ctp_example_cart'));
        }
        $methods = [];
        /**
         * @var ShippingMethod $shippingMethod
         */
        foreach ($shippingMethods as $shippingMethod) {
            $methods[$shippingMethod->getName()] = $shippingMethod->getName();
        }

        $entity = [];
        if ($cart->getShippingInfo()) {
            $entity['name'] = $cart->getShippingInfo()->getShippingMethodName();
        }
        $form = $this->createFormBuilder($entity)
            ->add('name', ChoiceType::class, [
                'choices'  => $methods,
                'expanded' => true,
            ])
            ->add('submit', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $shippingRepository = $this->get('commercetools.repository.shipping_method');
            $shippingMethod = $shippingRepository->getByName($request->getLocale(), $form->get('name')->getData());
            $cart = $this->get('commercetools.repository.cart')->setShippingMethod(
                $request->getLocale(),
                $cartId,
                $shippingMethod->getReference(),
                $customerId
            );

            return $this->redirect($this->generateUrl('_ctp_example_checkout_confirm'));
        }

        return $this->render('CtpBundle:checkout:checkoutShipping.html.twig', [
            'shipping_methods' => $shippingMethods,
            'form' => $form->createView()
        ]);
    }

    public function confirmationAction(Request $request)
    {
        $session = $this->get('session');

        $cartId = $session->get(CartRepository::CART_ID);
        $customerId = null;
        if ($this->get('security.token_storage')->getToken()->getUser() instanceof CtpUser) {
            $customerId = $this->get('security.token_storage')->getToken()->getUser()->getId();
        }
        $cart = $this->get('commercetools.repository.cart')->getCart($request->getLocale(), $cartId, $customerId);

        if (is_null($cart->getId())) {
            return $this->redirect($this->generateUrl('_ctp_example_cart'));
        }


        $customer = $this->get('commercetools.repository.customer')->getCustomer($request->getLocale(), $customerId);

        return $this->render('CtpBundle:cart:cartConfirm.html.twig',
            [
                'cart' => $cart,
                'customer' => $customer,
            ]
        );
    }

    public function successAction(Request $request)
    {
        $session = $this->get('session');
        $cartId = $session->get(CartRepository::CART_ID);
        $customerId = null;
        if ($this->get('security.token_storage')->getToken()->getUser() instanceof CtpUser) {
            $customerId = $this->get('security.token_storage')->getToken()->getUser()->getId();
        }
        $cart = $this->get('commercetools.repository.cart')->getCart($request->getLocale(), $cartId, $customerId);
        if (is_null($cart->getId())) {
            return $this->redirect($this->generateUrl('_ctp_example_cart'));
        }

        $repository = $this->get('commercetools.repository.order');

        $placeOrder = $repository->createOrderFromCart($request->getLocale(), $cart);

        return $this->render('CtpBundle:cart:cartSuccess.html.twig');
    }


    public function setAddressAction(Request $request)
    {
        $user = $this->getUser();
        if (!is_null($user)){
            $customerId = $this->get('security.token_storage')->getToken()->getUser()->getId();
            $customer = $this->get('commercetools.repository.customer')->getCustomer($request->getLocale(), $customerId);
        }else{
            $customerId = null;
            $customer = null;
        }

        $session = $this->get('session');
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->get('commercetools.repository.cart')->getCart($request->getLocale(), $cartId, $customerId);


        if (is_null($cart->getId())) {
            return $this->redirect($this->generateUrl('_ctp_example_cart'));
        }

        $entity = CartEntity::ofCart($cart);
        if (!is_null($customer) && count(array_diff_key($cart->getShippingAddress()->toArray(), ['country' => true])) == 0 ) {
            $address = $customer->getDefaultShippingAddress();
            $entity->shippingAddress = $address->toArray();
        }

        $form = $this->createFormBuilder($entity)
            ->add('check', CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'Shipping and Billing addresses are the same ',
                    'empty_data' => NULL
                ])
            ->add('shippingAddress', AddressType::class)
            ->add('billingAddress', AddressType::class)
            ->add('Submit', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);

        $repository = $this->get('commercetools.repository.cart');

        if ($form->isValid() && $form->isSubmitted()) {

            $check = $form->get('check')->getData();
            $shippingAddress = Address::fromArray($form->get('shippingAddress')->getData());

            $billingAddress = null;

            if($check !== true) {
                $billingAddress = Address::fromArray($form->get('billingAddress')->getData());
            }

            $cart = $repository->setAddresses(
                $request->getLocale(),
                $cartId,
                $shippingAddress,
                $billingAddress,
                $customerId
            );

            if (!is_null($cart)) {
                return $this->redirect($this->generateUrl('_ctp_example_checkout_shipping'));
            }
        }

        return $this->render('CtpBundle:checkout:checkout.html.twig',
        [
            'form' => $form->createView(),
        ]);
    }
}

