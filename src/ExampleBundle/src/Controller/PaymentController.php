<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Payment\PaymentReference;
use Commercetools\Core\Model\Payment\PaymentStatus;
use Commercetools\Core\Request\Carts\Command\CartAddPaymentAction;
use Commercetools\Core\Request\Orders\Command\OrderAddPaymentAction;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CtpBundle\Security\User\CtpUser;
use Commercetools\Symfony\StateBundle\Model\CtpMarkingStore\CtpMarkingStorePaymentState;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Commercetools\Core\Client;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param $paymentId
     * @param $orderId
     * @param CtpUser|null $user
     * @return Response
     */
    public function getPaymentAction(
        Request $request,
        SessionInterface $session,
        $paymentId,
        $orderId,
        CtpUser $user = null
    ) {
        $customerReference = is_null($user) ? null : CustomerReference::ofId($user->getId());
        $payment = $this->manager->getPaymentForUser($request->getLocale(), $paymentId, $customerReference, $session->getId());

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
     * @param $orderId
     * @param OrderManager $orderManager
     * @param CtpMarkingStorePaymentState $markingStorePaymentState
     * @param CtpUser|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createPaymentForOrderAction(
        Request $request,
        SessionInterface $session,
        OrderManager $orderManager,
        CtpMarkingStorePaymentState $markingStorePaymentState,
        $orderId,
        CtpUser $user = null
    ) {
        $order = $orderManager->getOrderForUser($request->getLocale(), $orderId, $user, $session->getId());

        if (!$order instanceof Order) {
            $this->addFlash('error', sprintf('Cannot find order: %s', $orderId));
            return $this->render('@Example/index.html.twig');
        }

        $payment = $this->createPayment($request->getLocale(), $order->getTotalPrice(), $session, $markingStorePaymentState, $user);

        if (!$payment instanceof Payment) {
            $this->addFlash('error', $payment->getMessage());
            return $this->render('@Example/index.html.twig');
        }

        // attach payment to order
        $orderBuilder = $orderManager->update($order);
        $orderBuilder->addAction(
            OrderAddPaymentAction::of()->setPayment(PaymentReference::ofId($payment->getId()))
        );
        $orderBuilder->flush();

        return $this->redirect($this->generateUrl('_ctp_example_order', ['orderId' => $orderId]));
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param CartManager $cartManager
     * @param CtpMarkingStorePaymentState $markingStorePaymentState
     * @param CtpUser|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function createPaymentForCartAction(
        Request $request,
        SessionInterface $session,
        CartManager $cartManager,
        CtpMarkingStorePaymentState $markingStorePaymentState,
        CtpUser $user = null
    ) {
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $cartManager->getCart($request->getLocale(), $cartId, $user, $session->getId());

        if (!$cart instanceof Cart) {
            $this->addFlash('error', 'Error finding cart');
            return $this->render('@Example/index.html.twig');
        }

        $payment = $this->createPayment($request->getLocale(), $cart->getTotalPrice(), $session, $markingStorePaymentState, $user);

        if (!$payment instanceof Payment) {
            $this->addFlash('error', $payment->getMessage());
            return $this->render('@Example/cart/cartConfirm.html.twig');
        }

        // attach payment to cart
        $cartBuilder = $cartManager->update($cart);
        $cartBuilder->addAction(
            CartAddPaymentAction::of()->setPayment(PaymentReference::ofId($payment->getId()))
        );
        $cartBuilder->flush();

        return $this->redirect($this->generateUrl('_ctp_example_checkout_confirm'));
    }

    /**
     * @param $locale
     * @param Money $totalPrice
     * @param SessionInterface $session
     * @param CtpMarkingStorePaymentState $markingStorePaymentState
     * @param CtpUser|null $user
     * @return Payment
     */
    public function createPayment(
        $locale,
        Money $totalPrice,
        SessionInterface $session,
        CtpMarkingStorePaymentState $markingStorePaymentState,
        CtpUser $user = null
    ) {
        $paymentStatus = PaymentStatus::of()
            ->setInterfaceText('Paypal')
            ->setState($markingStorePaymentState->getStateReferenceOfInitial());

        $customerReference = is_null($user) ? null : CustomerReference::ofId($user->getId());
        $payment = $this->manager->createPayment(
            $locale, $totalPrice, $customerReference, $session->getId(), $paymentStatus
        );

        return $payment;
    }

    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param OrderManager $orderManager
     * @param $toState
     * @param $paymentId
     * @param CtpUser|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function updatePaymentAction(
        Request $request,
        SessionInterface $session,
        OrderManager $orderManager,
        $toState,
        $paymentId,
        CtpUser $user = null
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

        if ($toState === 'toCompleted') {
            // assuming there is only one payment, we update order status after a payment is completed
            $order = $orderManager->getOrderFromPayment($request->getLocale(), $payment->getId(), $user, $session->getId());

            if ($order instanceof Order) {
                try {
                    $workflow = $this->workflows->get($order);
                } catch (InvalidArgumentException $e) {
                    $this->addFlash('error', 'Cannot find proper workflow configuration for Orders. Action aborted');
                    return $this->render('@Example/index.html.twig');
                }

                if ($workflow->can($order, 'toPaid')) {
                    $workflow->apply($order, 'toPaid');
                }

                $orderId = $order->getId();
            }
        }

        if (is_null($orderId)) {
            return $this->redirect($this->generateUrl('_ctp_example_orders_all'));
        }

        return $this->redirect($this->generateUrl('_ctp_example_order', ['orderId' => $orderId]));

    }
}
