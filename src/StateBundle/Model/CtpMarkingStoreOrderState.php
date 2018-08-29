<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Orders\Command\OrderTransitionStateAction;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
use Commercetools\Symfony\StateBundle\Cache\StateCacheHelper;
use Symfony\Component\Workflow\Marking;

class CtpMarkingStoreOrderState extends CtpMarkingStore
{
    private $orderManager;

    public function __construct(StateCacheHelper $cacheHelper, $initialState, OrderManager $manager)
    {
        parent::__construct($cacheHelper, $initialState);
        $this->orderManager = $manager;
    }

    public function setMarking($subject, Marking $marking)
    {
        $toState = current(array_keys($marking->getPlaces(), 1));

        $orderBuilder = new OrderUpdateBuilder($subject, $this->orderManager);
        $orderBuilder->addAction(
            OrderTransitionStateAction::ofState(
                StateReference::ofKey($toState)
            )->setForce(true)
        );

        $orderBuilder->flush();
    }

}
