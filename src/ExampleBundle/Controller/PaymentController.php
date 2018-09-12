<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Payment\PaymentStatus;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\StateBundle\Cache\StateKeyResolver;
use Commercetools\Symfony\StateBundle\Model\CtpMarkingStorePaymentState;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Commercetools\Core\Client;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\Exception\InvalidArgumentException;
use Symfony\Component\Workflow\Registry;


class PaymentController extends Controller
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

    public function getPaymentAction(Request $request, SessionInterface $session, UserInterface $user = null, $paymentId, $orderId)
    {
        if(is_null($user)){
            $payments = $this->manager->getPaymentForAnonymous($request->getLocale(), $paymentId, $session->getId());
        } else {
            $payments = $this->manager->getPaymentForCustomer($request->getLocale(), $paymentId, CustomerReference::ofId($user->getId()));
        }

        $payment = $payments->current();

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
     * @param CtpMarkingStorePaymentState $markingStore
     * @param StateKeyResolver $stateKeyResolver
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createPaymentForOrderAction(
        Request $request,
        SessionInterface $session,
        UserInterface $user = null,
        $orderId = null,
        OrderManager $orderManager,
        CtpMarkingStorePaymentState $markingStore,
        StateKeyResolver $stateKeyResolver
    ) {
        if(is_null($user)){
            $orders = $orderManager->getOrderForAnonymous($request->getLocale(), $session->getId(), $orderId);
        } else {
            $orders = $orderManager->getOrderForCustomer($request->getLocale(), $user->getId(), $orderId);
        }

        if (!$orders instanceof OrderCollection) {
            $this->addFlash('error', $orders->getMessage());
            return $this->render('@Example/index.html.twig');
        }

        $order = $orders->current();

        if (!$order instanceof Order) {
            $this->addFlash('error', 'order not found');
            return $this->render('@Example/index.html.twig');
        }

        $initialState = $markingStore->getMarking(Payment::of());
        $initialStateKey = current(array_keys($initialState->getPlaces()));

        $stateId = $stateKeyResolver->resolveKey($initialStateKey);

        $paymentStatus = PaymentStatus::of()
            ->setInterfaceText('Paypal')
            ->setState(StateReference::ofId($stateId));

        if(is_null($user)){
            $payment = $this->manager->createPaymentForAnonymous($request->getLocale(), $order->getTotalPrice(), $session->getId(), $paymentStatus, $order);
        } else {
            $customerReference = CustomerReference::ofId($user->getId());
            $payment = $this->manager->createPaymentForCustomer($request->getLocale(), $order->getTotalPrice(), $customerReference, $paymentStatus, $order);
        }

        if (!$payment instanceof Payment) {
            $this->addFlash('error', $payment->getMessage());
            return $this->render('@Example/index.html.twig');
        }

        return $this->redirect($this->generateUrl('_ctp_example_order', ['orderId' => $orderId]));
    }

    public function createPaymentForCartAction(
        Request $request,
        SessionInterface $session,
        UserInterface $user = null,
        CartManager $cartManager,
        CtpMarkingStorePaymentState $markingStore,
        StateKeyResolver $stateKeyResolver
    )
    {
        $cartId = $session->get(CartRepository::CART_ID);

        $cart = $cartManager->getCart($request->getLocale(), $cartId, $user, $session->getId());

        if (!$cart instanceof Cart) {
            $this->addFlash('error', 'Error finding cart');
            return $this->render('@Example/index.html.twig');
        }

        $initialState = $markingStore->getMarking(Payment::of());
        $initialStateKey = current(array_keys($initialState->getPlaces()));

        $stateId = $stateKeyResolver->resolveKey($initialStateKey);

        $paymentStatus = PaymentStatus::of()
            ->setInterfaceText('Paypal')
            ->setState(StateReference::ofId($stateId));

        if(is_null($user)){
            $payment = $this->manager->createPaymentForAnonymous($request->getLocale(), $cart->getTotalPrice(), $session->getId(), $paymentStatus);
        } else {
            $customerReference = CustomerReference::ofId($user->getId());
            $payment = $this->manager->createPaymentForCustomer($request->getLocale(), $cart->getTotalPrice(), $customerReference, $paymentStatus);
        }

        if (!$payment instanceof Payment) {
            $this->addFlash('error', $payment->getMessage());
            return $this->render('@Example/index.html.twig');
        }

        return $this->redirect($this->generateUrl('_ctp_example_checkout_confirm'));
    }

    public function updatePaymentAction(Request $request, SessionInterface $session, UserInterface $user = null, $toState, $paymentId)
    {
        if(is_null($user)){
            $payments = $this->manager->getPaymentForAnonymous($request->getLocale(), $paymentId,  $session->getId());
        } else {
            $payments = $this->manager->getPaymentForCustomer($request->getLocale(), $paymentId,  CustomerReference::ofId($user->getId()));
        }

        $payment = $payments->current();

        if (!$payment instanceof Payment) {
            $this->addFlash('error', 'Cannot find payment');
            return $this->render('@Example/index.html.twig');
        }

        // updates payment status
        try {
            $workflow = $this->workflows->get($payment);
        } catch (InvalidArgumentException $e) {
            $this->addFlash('error', 'Cannot find proper workflow configuration. Action aborted');
            return $this->render('@Example/index.html.twig');
        }

        if (!$workflow->can($payment, $toState)) {
            $this->addFlash('error', 'Cannot perform this action');
            return $this->render('@Example/index.html.twig');
        }

        $workflow->apply($payment, $toState);

        return $this->redirect($this->generateUrl('_ctp_example_order', ['orderId' => $request->get('orderId')]));

    }
}
