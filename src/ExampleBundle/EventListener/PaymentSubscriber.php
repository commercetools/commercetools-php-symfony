<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\EventListener;

use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Payment\PaymentReference;
use Commercetools\Core\Request\Carts\Command\CartAddPaymentAction;
use Commercetools\Core\Request\Orders\Command\OrderAddPaymentAction;
use Commercetools\Symfony\CartBundle\Event\PaymentPostCreateEvent;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Exception\InvalidArgumentException;
use Symfony\Component\Workflow\Registry;

class PaymentSubscriber implements EventSubscriberInterface
{
    /**
     * @var OrderManager
     */
    private $orderManager;

    /**
     * @var CartManager
     */
    private $cartManager;

    /**
     * @var Registry
     */
    private $workflows;

    public function __construct(OrderManager $orderManager, CartManager $cartManager, Registry $workflows)
    {
        $this->orderManager = $orderManager;
        $this->cartManager = $cartManager;
        $this->workflows = $workflows;
    }

    public static function getSubscribedEvents()
    {
        return [
            PaymentPostCreateEvent::class => 'onPaymentPostCreate',
            'workflow.PaymentState.transition.toCompleted' => 'onPaymentCompleted'
        ];
    }

    public function onPaymentPostCreate(PaymentPostCreateEvent $event)
    {
        if (!is_null($event->getPayment()->getCustom())) {
            $orderId = $event->getPayment()->getCustom()->getFields()->getFieldAsString('orderReference');
            $cartId = $event->getPayment()->getCustom()->getFields()->getFieldAsString('cartReference');

            if (!empty($orderId)) {
                $order = $this->orderManager->getOrderById($event->getPayment()->getContext()->getLocale(), $orderId);

                $orderBuilder = $this->orderManager->update($order);
                $orderBuilder->addAction(
                    OrderAddPaymentAction::of()->setPayment(PaymentReference::ofId($event->getPayment()->getId()))
                );
                $orderBuilder->flush();
            } elseif (!empty($cartId)) {
                $cart = $this->cartManager->getCart($event->getPayment()->getContext()->getLocale(), $cartId);

                $cartBuilder = $this->cartManager->update($cart);
                $cartBuilder->addAction(
                    CartAddPaymentAction::of()->setPayment(PaymentReference::ofId($event->getPayment()->getId()))
                );
                $cartBuilder->flush();
            }
        }

        return true;
    }

    public function onPaymentCompleted(Event $event)
    {
        if (!is_null($event->getSubject()->getCustom())) {
            $orderId = $event->getSubject()->getCustom()->getFields()->getFieldAsString('orderReference');

            // assuming there is only one payment, we update order status after one payment is completed
            $order = $this->orderManager->getOrderById($event->getSubject()->getContext()->getLocale(), $orderId);

            if ($order instanceof Order) {
                try {
                    $workflow = $this->workflows->get($order);
                } catch (InvalidArgumentException $e) {
                    $this->addFlash('error', 'Related Order could not be updated');
                    return false;
                }

                if ($workflow->can($order, 'toPaid')) {
                    $workflow->apply($order, 'toPaid');
                } else {
                    $this->addFlash('error', 'Related Order could not be updated');
                }
            }
        }

        return true;
    }
}
