<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\EventSubscriber;


use Commercetools\Symfony\CartBundle\Event\CartCreateEvent;
use Commercetools\Symfony\CartBundle\Event\CartPostCreateEvent;
use Commercetools\Symfony\CartBundle\Event\CartPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\CartUpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartSubscriber implements EventSubscriberInterface
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return [
            CartCreateEvent::class => 'onCartCreate',
            CartPostCreateEvent::class => 'onCartPostCreate',
            CartUpdateEvent::class => 'onCartUpdate',
            CartPostUpdateEvent::class => 'onCartPostUpdate'
        ];
    }

    public function onCartCreate()
    {
        return true;
    }

    public function onCartPostCreate()
    {
        return true;
    }

    public function onCartPostUpdate()
    {
        return true;
    }

    public function onCartUpdate(CartUpdateEvent $event)
    {
        return true;
    }
}
