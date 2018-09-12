<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\EventListener;


use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Payment\PaymentReference;
use Commercetools\Core\Request\Carts\Command\CartAddPaymentAction;
use Commercetools\Core\Request\Orders\Command\OrderAddPaymentAction;
use Commercetools\Symfony\CartBundle\Event\PaymentPostCreateEvent;
use Commercetools\Symfony\CartBundle\Event\PaymentPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Registry;

class PaymentSubscriber implements EventSubscriberInterface
{
    private $orderManager;
    private $cartManager;
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
        dump(['onPaymentPostCreate', $event]);

        if ($event->getBelongsTo() instanceof Cart) {
            $cartBuilder = $this->cartManager->update($event->getBelongsTo());
            $cartBuilder->addAction(
                CartAddPaymentAction::of()->setPayment(PaymentReference::ofId($event->getPayment()->getId()))
            );

            $cartBuilder->flush();
        } elseif ($event->getBelongsTo() instanceof Order) {
            $orderBuilder = $this->orderManager->update($event->getBelongsTo());
            $orderBuilder->addAction(
                OrderAddPaymentAction::of()->setPayment(PaymentReference::ofId($event->getPayment()->getId()))
            );

            $orderBuilder->flush();
        }

    }

    public function onPaymentCompleted(Event $event)
    {
        dump(['onPaymentCompleted', $event]);
        return;
        // TODO

        $order = $event->getOrder();
        $actions = $event->getActions();

        try {
            $workflow = $this->workflows->get($order);
        } catch (InvalidArgumentException $e) {
            return 1;
        }

        if ($workflow->can($order, 'toPaid')) {
            $workflow->apply($order, 'toPaid');
        }
    }
}
