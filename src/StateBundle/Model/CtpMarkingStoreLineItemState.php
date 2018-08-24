<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Orders\Command\OrderTransitionLineItemStateAction;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
use Symfony\Component\Workflow\Marking;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use Psr\Cache\CacheItemPoolInterface;

class CtpMarkingStoreLineItemState extends CtpMarkingStore
{
    private $orderManager;
    private $cartManager;

    public function __construct(StateRepository $stateRepository, CacheItemPoolInterface $cache, $initialState, OrderManager $manager, CartManager $cartManager)
    {
        parent::__construct($stateRepository, $cache, $initialState);
        $this->orderManager = $manager;
        $this->cartManager = $manager;
    }
    public function setMarking($subject, Marking $marking)
    {
        $rootOfSubject = $subject->rootGet()->current();

        if ($rootOfSubject instanceof Order) {
            dump('use OrderManager');
            $this->updateOrderItemState($subject, $marking, $rootOfSubject);

        } elseif ($rootOfSubject instanceof Cart) {
            dump('use CartManager');
        }
    }

    private function updateOrderItemState($subject, $marking, $order)
    {
        $toState = current(array_keys($marking->getPlaces(), 1));
        // TODO requires to get Parent Object!
        $lineItem = $subject->parentGet();

        $orderBuilder = new OrderUpdateBuilder($order, $this->orderManager);
        $orderBuilder->addAction(
            OrderTransitionLineItemStateAction::ofLineItemIdQuantityAndFromToState(
                $lineItem->getId(), $subject->quantity(), $subject->getState(), StateReference::ofTypeAndKey('state', $toState)
            )
        );

        $orderBuilder->flush();
    }
}
