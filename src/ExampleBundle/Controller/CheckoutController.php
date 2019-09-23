<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\ShippingMethod\ShippingMethod;
use Commercetools\Core\Model\ShippingMethod\ShippingMethodReference;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Carts\Command\CartSetBillingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingMethodAction;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Manager\MeCartManager;
use Commercetools\Symfony\CartBundle\Manager\MeOrderManager;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Manager\ShippingMethodManager;
use Commercetools\Symfony\ExampleBundle\Entity\CartEntity;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddressType;
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
     * @var MeCartManager
     */
    private $meCartManager;

    /**
     * CheckoutController constructor.
     * @param CartManager $cartManager
     * @param ShippingMethodManager $shippingMethodManager
     * @param OrderManager $orderManager
     * @param MeCartManager $meCartManager
     */
    public function __construct(
        CartManager $cartManager,
        ShippingMethodManager $shippingMethodManager,
        OrderManager $orderManager,
        MeCartManager $meCartManager
    ) {
        $this->cartManager = $cartManager;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->orderManager = $orderManager;
        $this->meCartManager = $meCartManager;
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

        return $this->render('@Example/checkout/secureCheckout.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function shippingMethodAction(Request $request)
    {

        $cart = $this->meCartManager->getCart($request->getLocale());
        $shippingMethods = $this->shippingMethodManager->getShippingMethodsByCart($request->getLocale(), $cart->getId());

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
            $cartBuilder = $this->meCartManager->update($cart);
            $cartBuilder->addAction(
                CartSetShippingMethodAction::of()->setShippingMethod(
                    ShippingMethodReference::ofId($form->get('name')->getData())
                )
            );
            $cartBuilder->flush();

            return $this->redirect($this->generateUrl('_ctp_example_checkout_confirm'));
        }

        return $this->render('@Example/checkout-shipping.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function reviewOrderDetailsAction(Request $request, UserInterface $user = null)
    {
        $cart = $this->meCartManager->getCart($request->getLocale());

        if (is_null($cart) || is_null($cart->getId())) {
            return $this->redirect($this->generateUrl('_ctp_example_cart'));
        }

        return $this->render('@Example/checkout-confirmation.html.twig', [
            'cart' => $cart,
            'customer' => $user,
        ]);
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * //     * @param CtpMarkingStoreOrderState $markingStoreOrderState
     * @param MeOrderManager $meOrderManager
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function placeCartToOrderAction(
        Request $request,
        SessionInterface $session,
        MeOrderManager $meOrderManager,
        //        CtpMarkingStoreOrderState $markingStoreOrderState,
        UserInterface $user = null
    ) {
        $cart = $this->meCartManager->getCart($request->getLocale());

        if (is_null($cart)) {
            return $this->redirect($this->generateUrl('_ctp_example_cart'));
        }

        // requires admin privileges to set order states etc

//        $markingStoreOrderState = $this->container->get('Commercetools\Symfony\StateBundle\Model\CtpMarkingStore\CtpMarkingStoreOrderState');
//        if ($this->container->has('state_machine.OrderState')) {
//            $markingStoreOrderState = $this->container->has('state_machine.OrderState');
//        }

//        $order = $this->orderManager->createOrderFromCart(
//            $request->getLocale(),
//            $cart,
//            $markingStoreOrderState->getStateReferenceOfInitial()
//        );

        $order = $meOrderManager->createOrderFromCart($request->getLocale(), $cart);

        return $this->render('@Example/checkout-thankyou.html.twig', [
            'order' => $order
        ]);
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function setAddressAction(Request $request, UserInterface $user = null)
    {
        $cart = $this->meCartManager->getCart($request->getLocale());

        if (is_null($cart) || is_null($cart->getId())) {
            // add error message
            return $this->redirect($this->generateUrl('_ctp_example_cart'));
        }

        $entity = CartEntity::ofCart($cart);
        if (!is_null($user) && !is_null($user->getDefaultShippingAddress()) && count(array_diff_key($cart->getShippingAddress()->toArray(), ['country' => true])) == 0) {
            $entity->setShippingAddress($user->getDefaultShippingAddress()->toArray());
        }

        $form = $this->createFormBuilder($entity)
            ->add(
                'differentAddresses',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'Shipping and Billing addresses are the same ',
                    'empty_data' => null
                ]
            )
            ->add('shippingAddress', AddressType::class)
            ->add('billingAddress', AddressType::class)
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $differentAddresses = $form->get('differentAddresses')->getData();
            $shippingAddress = Address::fromArray($form->get('shippingAddress')->getData());

            $billingAddress = $differentAddresses ?
                Address::fromArray($form->get('billingAddress')->getData()) : $shippingAddress;

            $cartBuilder = $this->meCartManager->update($cart);
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
            'cart' => $cart
        ]);
    }
}
