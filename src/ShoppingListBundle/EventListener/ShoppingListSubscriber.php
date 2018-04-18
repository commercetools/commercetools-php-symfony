<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 17/04/2018
 * Time: 15:36
 */

namespace Commercetools\Symfony\ShoppingListBundle\EventListener;

use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListAddLineItemAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListRemoveLineItemAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeLineItemQuantityAction;
use Commercetools\Symfony\ShoppingListBundle\Event\ShoppingListUpdateEvent;
use Commercetools\Symfony\ShoppingListBundle\Event\UpdateEvent;
use Commercetools\Symfony\ShoppingListBundle\ShoppingListEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShoppingListSubscriber implements EventSubscriberInterface
{
    public function onUpdateAction(ShoppingListUpdateEvent $event)
    {
        error_log("raised. Yeah!");
    }

    public static function getSubscribedEvents()
    {
        return [
            ShoppingListAddLineItemAction::class => 'onUpdateAction',
            ShoppingListRemoveLineItemAction::class => 'onUpdateAction',
            ShoppingListChangeLineItemQuantityAction::class => 'onUpdateAction'
        ];
    }


}