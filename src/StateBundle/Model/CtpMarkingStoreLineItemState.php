<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
use Commercetools\Symfony\StateBundle\Cache\StateCacheHelper;
use Symfony\Component\Workflow\Marking;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;

class CtpMarkingStoreLineItemState extends CtpMarkingStore
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

        $orderBuilder = new OrderUpdateBuilder($subject->getResource(), $this->orderManager);

        $orderBuilder->addAction($subject->getUpdateAction($toState));

        $orderBuilder->flush();
    }
}
