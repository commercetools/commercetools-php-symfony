<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\EventListener;


use Commercetools\Symfony\CartBundle\Event\PaymentPostCreateEvent;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
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
//        dump(['onPaymentPostCreate', $event]);
        return true;
    }

    public function onPaymentCompleted(Event $event)
    {
//        dump(['onPaymentCompleted', $event]);
        return true;
    }
}
