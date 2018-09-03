<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Orders\Command\OrderTransitionStateAction;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
use Symfony\Component\Workflow\Event\Event;

class OrderStateTransitionHandler implements SubjectHandler
{
    private $manager;

    public function __construct(OrderManager $manager)
    {
        $this->manager = $manager;
    }


    public function handle(Event $event)
    {
        $orderBuilder = new OrderUpdateBuilder($event->getSubject(), $this->manager);
        $orderBuilder->addAction(
            OrderTransitionStateAction::ofState(
                StateReference::ofKey(current($event->getTransition()->getTos()))
            )
        );

        $orderBuilder->flush();
    }
}
