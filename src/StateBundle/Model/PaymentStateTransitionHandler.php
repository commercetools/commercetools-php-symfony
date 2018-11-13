<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Payments\Command\PaymentTransitionStateAction;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;
use Commercetools\Symfony\CartBundle\Model\PaymentUpdateBuilder;
use Symfony\Component\Workflow\Event\Event;

class PaymentStateTransitionHandler implements SubjectHandler
{
    private $manager;

    public function __construct(PaymentManager $manager)
    {
        $this->manager = $manager;
    }

    public function handle(Event $event)
    {
        $orderBuilder = new PaymentUpdateBuilder($event->getSubject(), $this->manager);
        $orderBuilder->addAction(
            PaymentTransitionStateAction::ofState(
                StateReference::ofKey(current($event->getTransition()->getTos()))
            )
        );

        $orderBuilder->flush();
    }
}
