<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
use Symfony\Component\Workflow\Marking;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use Psr\Cache\CacheItemPoolInterface;

class CtpMarkingStoreLineItemState extends CtpMarkingStore
{
    private $orderManager;

    public function __construct(StateRepository $stateRepository, CacheItemPoolInterface $cache, $initialState, OrderManager $manager)
    {
        parent::__construct($stateRepository, $cache, $initialState);
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
