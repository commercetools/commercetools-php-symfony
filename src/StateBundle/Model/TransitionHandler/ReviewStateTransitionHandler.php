<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model\TransitionHandler;

use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Reviews\Command\ReviewTransitionStateAction;
use Commercetools\Symfony\ReviewBundle\Manager\ReviewManager;
use Commercetools\Symfony\ReviewBundle\Model\ReviewUpdateBuilder;
use Symfony\Component\Workflow\Event\Event;

class ReviewStateTransitionHandler implements SubjectHandler
{
    private $manager;

    public function __construct(ReviewManager $manager)
    {
        $this->manager = $manager;
    }


    public function handle(Event $event)
    {
        $orderBuilder = new ReviewUpdateBuilder($event->getSubject(), $this->manager);
        $orderBuilder->addAction(
            ReviewTransitionStateAction::ofState(
                StateReference::ofKey(current($event->getTransition()->getTos()))
            )
        );

        $orderBuilder->flush();
    }
}
