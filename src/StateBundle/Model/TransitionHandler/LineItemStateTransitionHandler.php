<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model\TransitionHandler;

use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
use Symfony\Component\Workflow\Event\Event;

class LineItemStateTransitionHandler implements SubjectHandler
{
    private $manager;

    public function __construct(OrderManager $manager)
    {
        $this->manager = $manager;
    }


    public function handle(Event $event)
    {
        $subject = $event->getSubject();
        $orderBuilder = new OrderUpdateBuilder($subject->getResource(), $this->manager);

        $orderBuilder->addAction($subject->getUpdateAction(current($event->getTransition()->getTos())));

        $orderBuilder->flush();
    }
}
