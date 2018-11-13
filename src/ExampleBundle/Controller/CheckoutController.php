<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\ShippingMethod\ShippingMethod;
use Commercetools\Core\Request\Carts\Command\CartSetBillingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingMethodAction;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Manager\ShippingMethodManager;
use Commercetools\Symfony\CartBundle\Entity\CartEntity;
use Commercetools\Symfony\CustomerBundle\Security\User\User;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddressType;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class CheckoutController extends Controller
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var CartManager
     */
    private $cartManager;

    /**
     * @var ShippingMethodManager
     */
    private $shippingMethodManager;

    /**
     * @var OrderManager
     */
    private $orderManager;

    /**
     * CheckoutController constructor.
     */
    public function __construct(
        Client $client,
        CartManager $cartManager,
        ShippingMethodManager $shippingMethodManager,
        OrderManager $orderManager
    )
    {
        $this->client = $client;
        $this->cartManager = $cartManager;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->orderManager = $orderManager;
    }

    public function signinAction(Request $request)
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirect($this->generateUrl('_ctp_example_checkout_address'));
        }

        $authenticationUtils = $this->get('security.authentication_utils');

        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('ExampleBundle:checkout:secureCheckout.html.twig',
            [
                'last_username' => $lastUsername,
                'error' => $error
            ]
        );
    }

    public function shippingMethodAction(Request $request, UserInterface $user = null)
    {
        $session = $this->get('session');
        $cartId = $session->get(CartRepository::CART_ID);
        $shippingMethods = $this->shippingMethodManager->getShippingMethodByCart($request->getLocale(), $cartId);

        $customerId = is_null($user) ? null : $user->getId();

        $cart = $this->cartManager->getCart($request->getLocale(), $cartId, $customerId);

        if (is_null($cart->getId())) {
            return $this->redirect($this->generateUrl('_ctp_example_cart'));
        }

        $methods = [];
        /**
         * @var ShippingMethod $shippingMethod
         */
        foreach ($shippingMethods as $shippingMethod) {
            $methods[$shippingMethod->getName()] = $shippingMethod->getId();
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
            $shippingMethod = $this->shippingMethodManager->getShippingMethodById($request->getLocale(), $form->get('name')->getData());
            $cartBuilder = $this->cartManager->update($cart);
            $cartBuilder->setActions([
                CartSetShippingMethodAction::of()->setShippingMethod($shippingMethod->getReference())
            ]);

            $cartBuilder->flush();

            return $this->redirect($this->generateUrl('_ctp_example_checkout_confirm'));
        }

        return $this->render('ExampleBundle:checkout:checkoutShipping.html.twig', [
            'shipping_methods' => $shippingMethods,
            'form' => $form->createView()
        ]);
    }

    public function confirmationAction(Request $request, UserInterface $user = null)
    {
        $session = $this->get('session');

        $cartId = $session->get(CartRepository::CART_ID);
        $customerId = is_null($user) ? null : $user->getId();

        $cart = $this->cartManager->getCart($request->getLocale(), $cartId, $customerId);

        if (is_null($cart->getId())) {
            return $this->redirect($this->generateUrl('_ctp_example_cart'));
        }

        return $this->render('ExampleBundle:cart:cartConfirm.html.twig',
            [
                'cart' => $cart,
                'customer' => $user,
            ]
        );
    }

    public function successAction(Request $request, UserInterface $user = null)
    {
        $session = $this->get('session');
        $cartId = $session->get(CartRepository::CART_ID);
        $customerId = is_null($user) ? null : $user->getId();

        $cart = $this->cartManager->getCart($request->getLocale(), $cartId, $customerId);
        if (is_null($cart->getId())) {
            return $this->redirect($this->generateUrl('_ctp_example_cart'));
        }

        $this->orderManager->createOrderFromCart($request->getLocale(), $cart);

        return $this->render('ExampleBundle:cart:cartSuccess.html.twig');
    }


    public function setAddressAction(Request $request, UserInterface $user = null)
    {
        $customerId = null;
        $customer = null;

        if (!is_null($user)){
            $customerId = $user->getId();
            $customer = $user;
        }

        $session = $this->get('session');
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->cartManager->getCart($request->getLocale(), $cartId, $customerId);


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

        if ($form->isSubmitted() && $form->isValid()) {

            $check = $form->get('check')->getData();
            $shippingAddress = Address::fromArray($form->get('shippingAddress')->getData());

            $billingAddress = $shippingAddress;

            if($check !== true) {
                $billingAddress = Address::fromArray($form->get('billingAddress')->getData());
            }

            $cartBuilder = $this->cartManager->update($cart);
            $cartBuilder
                ->setShippingAddress(CartSetShippingAddressAction::of()->setAddress($shippingAddress))
                ->setBillingAddress(CartSetBillingAddressAction::of()->setAddress($billingAddress));
            $cartBuilder->flush();


            if (!is_null($cart)) {
                return $this->redirect($this->generateUrl('_ctp_example_checkout_shipping'));
            }
        }

        return $this->render('ExampleBundle:checkout:checkout.html.twig',
        [
            'form' => $form->createView(),
        ]);
    }
}

