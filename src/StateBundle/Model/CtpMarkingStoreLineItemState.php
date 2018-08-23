<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Symfony\Component\Workflow\Marking;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Orders\Command\OrderTransitionStateAction;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
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
        // TODO: Implement setMarking() method.
        dump($subject);
        dump($marking);
    }
}
