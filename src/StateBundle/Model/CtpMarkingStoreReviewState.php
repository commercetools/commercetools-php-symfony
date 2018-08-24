<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Orders\Command\OrderTransitionStateAction;
use Commercetools\Core\Request\Reviews\Command\ReviewTransitionStateAction;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
use Commercetools\Symfony\ReviewBundle\Manager\ReviewManager;
use Commercetools\Symfony\ReviewBundle\Model\ReviewUpdateBuilder;
use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Workflow\Marking;

class CtpMarkingStoreReviewState extends CtpMarkingStore
{
    private $reviewManager;

    public function __construct(StateRepository $stateRepository, CacheItemPoolInterface $cache, $initialState, ReviewManager $manager)
    {
        parent::__construct($stateRepository, $cache, $initialState);
        $this->reviewManager = $manager;
    }

    public function setMarking($subject, Marking $marking)
    {
        $toState = current(array_keys($marking->getPlaces(), 1));

        $orderBuilder = new ReviewUpdateBuilder($subject, $this->reviewManager);
        $orderBuilder->addAction(
            ReviewTransitionStateAction::ofState(
                StateReference::ofTypeAndKey('state', $toState)
            )->setForce(true)
        );

        $orderBuilder->flush();
    }

}
