<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Reviews\Command\ReviewTransitionStateAction;
use Commercetools\Symfony\ReviewBundle\Manager\ReviewManager;
use Commercetools\Symfony\ReviewBundle\Model\ReviewUpdateBuilder;
use Commercetools\Symfony\StateBundle\Cache\StateCacheHelper;
use Symfony\Component\Workflow\Marking;

class CtpMarkingStoreReviewState extends CtpMarkingStore
{
    private $reviewManager;

    public function __construct(StateCacheHelper $cacheHelper, $initialState, ReviewManager $manager)
    {
        parent::__construct($cacheHelper, $initialState);
        $this->reviewManager = $manager;
    }

    public function setMarking($subject, Marking $marking)
    {
        $toState = current(array_keys($marking->getPlaces(), 1));

        $orderBuilder = new ReviewUpdateBuilder($subject, $this->reviewManager);
        $orderBuilder->addAction(
            ReviewTransitionStateAction::ofState(
                StateReference::ofKey($toState)
            )->setForce(true)
        );

        $orderBuilder->flush();
    }

}
