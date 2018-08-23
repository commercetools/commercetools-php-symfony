<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Order\Order;
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
        dump($subject);
        $rootOfSubject = $subject->rootGet()->current();

        if ($rootOfSubject instanceof Order) {
            dump('use OrderManager');
            $this->updateOrderItemState($subject, $marking);

        } elseif ($rootOfSubject instanceof Cart) {
            dump('use CartManager');
        }
    }

    private function updateOrderItemState($subject, $marking)
    {
        $stateKey = array_keys($marking->getPlaces(), 1);

        $orderBuilder = new OrderUpdateBuilder($subject, $this->orderManager);
        $orderBuilder->addAction(
            OrderTransitionLineItemStateAction::ofLineItemIdQuantityAndFromToState(

            )
        );

        $orderBuilder->flush();
    }
}
