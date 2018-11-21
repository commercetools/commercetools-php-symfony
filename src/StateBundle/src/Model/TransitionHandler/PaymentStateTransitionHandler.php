<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model\TransitionHandler;


use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Payments\Command\PaymentTransitionStateAction;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;
use Commercetools\Symfony\CartBundle\Model\PaymentUpdateBuilder;
use Symfony\Component\Workflow\Event\Event;

class PaymentStateTransitionHandler implements SubjectHandler
{
    /**
     * @var PaymentManager
     */
    private $manager;

    /**
     * PaymentStateTransitionHandler constructor.
     * @param PaymentManager $manager
     */
    public function __construct(PaymentManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param Event $event
     */
    public function handle(Event $event)
    {
        $subject = $event->getSubject();
        $orderBuilder = new PaymentUpdateBuilder($subject, $this->manager);
        $orderBuilder->addAction(
            PaymentTransitionStateAction::ofState(
                StateReference::ofKey(current($event->getTransition()->getTos()))
            )
        );

        $orderBuilder->flush();
    }
}
