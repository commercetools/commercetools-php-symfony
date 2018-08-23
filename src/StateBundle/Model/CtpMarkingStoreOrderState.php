<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Orders\Command\OrderTransitionStateAction;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Workflow\Marking;

class CtpMarkingStoreOrderState extends CtpMarkingStore
{
    private $orderManager;

    public function __construct(StateRepository $stateRepository, CacheItemPoolInterface $cache, $initialState, OrderManager $manager)
    {
        parent::__construct($stateRepository, $cache, $initialState);
        $this->orderManager = $manager;
    }

    public function setMarking($subject, Marking $marking)
    {
        $stateKey = array_keys($marking->getPlaces(), 1);

        $orderBuilder = new OrderUpdateBuilder($subject, $this->orderManager);
        $orderBuilder->addAction(
            OrderTransitionStateAction::ofState(
                StateReference::ofTypeAndKey('state', $stateKey[0])
            )->setForce(true)
        );

        $orderBuilder->flush();
    }

}
