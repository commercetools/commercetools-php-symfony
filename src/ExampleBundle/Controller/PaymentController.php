<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\CustomField\CustomFieldObjectDraft;
use Commercetools\Core\Model\CustomField\FieldContainer;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Payment\PaymentStatus;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\StateBundle\Model\CtpMarkingStore\CtpMarkingStorePaymentState;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Commercetools\Core\Client;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\Exception\InvalidArgumentException;
use Symfony\Component\Workflow\Registry;


class PaymentController extends AbstractController
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var PaymentManager
     */
    private $manager;

    /**
     * @var Registry
     */
    private $workflows;


    /**
     * OrderController constructor.
     * @param Client $client
     * @param PaymentManager $manager
     * @param Registry $workflows
     */
    public function __construct(Client $client, PaymentManager $manager, Registry $workflows)
    {
        $this->client = $client;
        $this->manager = $manager;
        $this->workflows = $workflows;
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param UserInterface|null $user
     * @param $paymentId
     * @param $orderId
     * @return Response
     */
    public function getPaymentAction(
        Request $request,
        SessionInterface $session,
        $paymentId,
        $orderId,
        UserInterface $user = null
    ) {
        $payment = $this->manager->getPaymentForUser($request->getLocale(), $paymentId, $user, $session->getId());

        if (!$payment instanceof Payment) {
            $this->addFlash('error', sprintf('Cannot find payment: %s', $paymentId));
            return new Response();
        }

        return $this->render( '@Example/partials/paymentInfo.html.twig', [
            'payment' => $payment,
            'orderId' => $orderId
        ]);
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param UserInterface|null $user
     * @param $orderId
     * @param OrderManager $orderManager
     * @param CtpMarkingStorePaymentState $markingStorePaymentState
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createPaymentForOrderAction(
        Request $request,
        SessionInterface $session,
        OrderManager $orderManager,
        CtpMarkingStorePaymentState $markingStorePaymentState,
        $orderId,
        UserInterface $user = null
    ) {
        $order = $orderManager->getOrderForUser($request->getLocale(), $orderId, $user, $session->getId());

        if (!$order instanceof Order) {
            $this->addFlash('error', sprintf('Cannot find order: %s', $orderId));
            return $this->render('@Example/index.html.twig');
        }

        $custom = null;
        if ($this->container->hasParameter('commercetools.custom_types')) {
            $customTypes = $this->container->getParameter('commercetools.custom_types');
            if (isset($customTypes['paymentsRelations'])) {
                $custom = CustomFieldObjectDraft::ofTypeKey('paymentsRelations')->setFields(
                    FieldContainer::of()->set('orderReference', $order->getId())
                );
            }
        }

        // TODO
//        $relationsType = $this->container->get('commercetools.custom_types')->get('paymentsRelations');
//        if (!is_null($relationsType)) {
//            $custom = CustomFieldObjectDraft::ofType($relationsType)->setFields(
//                FieldContainer::of()->set('orderReference', $order->getId())
//            );
//        }

        $payment = $this->createPayment($request->getLocale(), $order->getTotalPrice(), $session, $markingStorePaymentState, $user, $custom);

        if (!$payment instanceof Payment) {
            $this->addFlash('error', $payment->getMessage());
            return $this->render('@Example/index.html.twig');
        }

        return $this->redirect($this->generateUrl('_ctp_example_order', ['orderId' => $orderId]));
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param CartManager $cartManager
     * @param CtpMarkingStorePaymentState $markingStorePaymentState
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function createPaymentForCartAction(
        Request $request,
        SessionInterface $session,
        CartManager $cartManager,
        CtpMarkingStorePaymentState $markingStorePaymentState,
        UserInterface $user = null
    ) {
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $cartManager->getCart($request->getLocale(), $cartId, $user, $session->getId());

        if (!$cart instanceof Cart) {
            $this->addFlash('error', 'Error finding cart');
            return $this->render('@Example/index.html.twig');
        }

        $custom = null;
        if ($this->container->hasParameter('commercetools.custom_types')) {
            $customTypes = $this->container->getParameter('commercetools.custom_types');
            if (isset($customTypes['paymentsRelations'])) {
                $custom = CustomFieldObjectDraft::ofTypeKey('paymentsRelations')->setFields(
                    FieldContainer::of()->set('cartReference', $cart->getId())
                );
            }
        }

        $payment = $this->createPayment($request->getLocale(), $cart->getTotalPrice(), $session, $markingStorePaymentState, $user, $custom);

        if (!$payment instanceof Payment) {
            $this->addFlash('error', $payment->getMessage());
            return $this->render('@Example/cart/cartConfirm.html.twig');
        }

        return $this->redirect($this->generateUrl('_ctp_example_checkout_confirm'));
    }

    /**
     * @param $locale
     * @param Money $totalPrice
     * @param SessionInterface $session
     * @param CtpMarkingStorePaymentState $markingStorePaymentState
     * @param UserInterface|null $user
     * @param CustomFieldObjectDraft|null $customFieldObjectDraft
     * @return Payment
     */
    public function createPayment(
        $locale,
        Money $totalPrice,
        SessionInterface $session,
        CtpMarkingStorePaymentState $markingStorePaymentState,
        UserInterface $user = null,
        CustomFieldObjectDraft $customFieldObjectDraft = null
    ) {
        $paymentStatus = PaymentStatus::of()
            ->setInterfaceText('Paypal')
            ->setState($markingStorePaymentState->getStateReferenceOfInitial());

        $customerReference = is_null($user) ? null : CustomerReference::ofId($user->getId());
        $payment = $this->manager->createPaymentForUser(
            $locale, $totalPrice, $customerReference, $session->getId(), $paymentStatus, $customFieldObjectDraft
        );

        return $payment;
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param $toState
     * @param $paymentId
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function updatePaymentAction(
        Request $request,
        SessionInterface $session,
        $toState,
        $paymentId,
        UserInterface $user = null
    ) {
        $customerReference = is_null($user) ? null : CustomerReference::ofId($user->getId());
        $payment = $this->manager->getPaymentForUser($request->getLocale(), $paymentId, $customerReference, $session->getId());

        if (!$payment instanceof Payment) {
            $this->addFlash('error', 'Cannot find payment');
            return $this->render('@Example/index.html.twig');
        }

        try {
            $workflow = $this->workflows->get($payment);
        } catch (InvalidArgumentException $e) {
            $this->addFlash('error', 'Cannot find proper workflow configuration for Payments. Action aborted');
            return $this->render('@Example/index.html.twig');
        }

        if (!$workflow->can($payment, $toState)) {
            $this->addFlash('error', 'Cannot perform this action');
            return $this->render('@Example/index.html.twig');
        }

        $workflow->apply($payment, $toState);

        $orderId = $request->get('orderId');

        if (is_null($orderId)) {
            return $this->redirect($this->generateUrl('_ctp_example_orders_all'));
        }

        return $this->redirect($this->generateUrl('_ctp_example_order', ['orderId' => $orderId]));

    }
}
