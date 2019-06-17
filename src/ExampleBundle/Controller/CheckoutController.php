<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\ShippingMethod\ShippingMethod;
use Commercetools\Core\Model\ShippingMethod\ShippingMethodReference;
use Commercetools\Core\Request\Carts\Command\CartSetBillingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingMethodAction;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Manager\ShippingMethodManager;
use Commercetools\Symfony\ExampleBundle\Entity\CartEntity;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddressType;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\StateBundle\Model\CtpMarkingStore\CtpMarkingStoreOrderState;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class CheckoutController extends AbstractController
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
     * @param Client $client
     * @param CartManager $cartManager
     * @param ShippingMethodManager $shippingMethodManager
     * @param OrderManager $orderManager
     */
    public function __construct(
        Client $client,
        CartManager $cartManager,
        ShippingMethodManager $shippingMethodManager,
        OrderManager $orderManager
    ) {
        $this->client = $client;
        $this->cartManager = $cartManager;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->orderManager = $orderManager;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function signinAction(AuthenticationUtils $authenticationUtils)
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirect($this->generateUrl('_ctp_example_checkout_address'));
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('ExampleBundle:checkout:secureCheckout.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function shippingMethodAction(Request $request, SessionInterface $session, UserInterface $user = null)
    {
        $cartId = $session->get(CartRepository::CART_ID);
        $shippingMethods = $this->shippingMethodManager->getShippingMethodsByCart($request->getLocale(), $cartId);

        $cart = $this->cartManager->getCart($request->getLocale(), $cartId, $user, $session->getId());

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
            $cartBuilder = $this->cartManager->update($cart);
            $cartBuilder->setActions([
                CartSetShippingMethodAction::of()->setShippingMethod(
                    ShippingMethodReference::ofId($form->get('name')->getData())
                )
            ]);

            $cartBuilder->flush();

            return $this->redirect($this->generateUrl('_ctp_example_checkout_confirm'));
        }

        return $this->render('ExampleBundle:checkout:checkoutShipping.html.twig', [
            'shipping_methods' => $shippingMethods,
            'form' => $form->createView()
        ]);
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function reviewOrderDetailsAction(Request $request, SessionInterface $session, UserInterface $user = null)
    {
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->cartManager->getCart($request->getLocale(), $cartId, $user, $session->getId());

        if (is_null($cart) || is_null($cart->getId())) {
            return $this->redirect($this->generateUrl('_ctp_example_cart'));
        }

        return $this->render('ExampleBundle:cart:cartConfirm.html.twig', [
            'cart' => $cart,
            'customer' => $user,
        ]);
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param CtpMarkingStoreOrderState $markingStoreOrderState
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function placeCartToOrderAction(
        Request $request,
        SessionInterface $session,
        CtpMarkingStoreOrderState $markingStoreOrderState,
        UserInterface $user = null
    ) {
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->cartManager->getCart($request->getLocale(), $cartId, $user, $session->getId());

        if (is_null($cart->getId())) {
            return $this->redirect($this->generateUrl('_ctp_example_cart'));
        }

        $order = $this->orderManager->createOrderFromCart(
            $request->getLocale(),
            $cart,
            $markingStoreOrderState->getStateReferenceOfInitial()
        );

        return $this->render('ExampleBundle:cart:cartSuccess.html.twig', [
            'orderId' => $order->getId()
        ]);
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function setAddressAction(Request $request, SessionInterface $session, UserInterface $user = null)
    {
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->cartManager->getCart($request->getLocale(), $cartId, $user, $session->getId());

        if (is_null($cart->getId())) {
            return $this->redirect($this->generateUrl('_ctp_example_cart'));
        }

        $entity = CartEntity::ofCart($cart);
        if (!is_null($user) && count(array_diff_key($cart->getShippingAddress()->toArray(), ['country' => true])) == 0) {
            $entity->setShippingAddress($user->getDefaultShippingAddress()->toArray());
        }

        $form = $this->createFormBuilder($entity)
            ->add(
                'check',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'Shipping and Billing addresses are the same ',
                    'empty_data' => null
                ]
            )
            ->add('shippingAddress', AddressType::class)
            ->add('billingAddress', AddressType::class)
            ->add('Submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $check = $form->get('check')->getData();
            $shippingAddress = Address::fromArray($form->get('shippingAddress')->getData());

            $billingAddress = $shippingAddress;

            if ($check !== true) {
                $billingAddress = Address::fromArray($form->get('billingAddress')->getData());
            }

            $cartBuilder = $this->cartManager->update($cart);
            $cartBuilder
                ->setShippingAddress(CartSetShippingAddressAction::of()->setAddress($shippingAddress))
                ->setBillingAddress(CartSetBillingAddressAction::of()->setAddress($billingAddress));
            $cart = $cartBuilder->flush();

            if (!is_null($cart)) {
                return $this->redirect($this->generateUrl('_ctp_example_checkout_shipping'));
            }
        }

        return $this->render('@Example/checkout-address.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
