<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Model;


use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Orders\Command\OrderTransitionStateAction;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;

class OrderWrapper extends Order
{
    /**
     * @var OrderManager
     */
    private $orderManager;

    public function getStateKey()
    {
        $stateReference = parent::getState();

        if (is_null($stateReference)) {
            return 'created';
        }

        $obj = $stateReference->getobj();

        return $obj->getKey();
    }

    public function setStateKey($stateKey)
    {
        $orderBuilder = new OrderUpdateBuilder($this, $this->orderManager);
        $orderBuilder->addAction(
            OrderTransitionStateAction::ofState(StateReference::ofTypeAndKey('state', $stateKey))->setForce(true)
        );

        $orderBuilder->flush();
    }

    /**
     * @return OrderManager
     */
    public function getOrderManager()
    {
        return $this->orderManager;
    }

    /**
     * @param OrderManager $orderManager
     */
    public function setOrderManager($orderManager)
    {
        $this->orderManager = $orderManager;
    }
}
