<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\EventListener;


use Commercetools\Symfony\CartBundle\Event\OrderCreateEvent;
use Commercetools\Symfony\CartBundle\Event\OrderPostCreateEvent;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OrderSubscriber implements EventSubscriberInterface
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return [
            OrderCreateEvent::class => 'onOrderCreate',
            OrderPostCreateEvent::class => 'onOrderPostCreate'
        ];
    }

    public function onOrderCreate()
    {
        return true;
    }

    public function onOrderPostCreate(OrderPostCreateEvent $event)
    {
        $this->session->remove(CartRepository::CART_ID);
        $this->session->remove(CartRepository::CART_ITEM_COUNT);
    }


}
