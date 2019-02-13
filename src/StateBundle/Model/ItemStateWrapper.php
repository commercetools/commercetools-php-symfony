<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;

use Commercetools\Core\Model\Cart\CustomLineItem;
use Commercetools\Core\Model\Cart\LineItem;
use Commercetools\Core\Model\Common\Resource;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Orders\Command\OrderTransitionCustomLineItemStateAction;
use Commercetools\Core\Request\Orders\Command\OrderTransitionLineItemStateAction;

class ItemStateWrapper implements StateWrapper
{

    /**
     * @var Resource $resource
     */
    private $resource;

    /**
     * @var int $quantity
     */
    private $quantity;

    /**
     * @var StateReference $stateReference
     */
    private $stateReference;

    /**
     * @var LineItem $lineItem
     */
    private $lineItem;

    /**
     * @var CustomLineItem $customLineItem
     */
    private $customLineItem;

    /**
     * @return LineItem
     */
    public function getLineItem()
    {
        return $this->lineItem;
    }

    /**
     * @return CustomLineItem
     */
    public function getCustomLineItem()
    {
        return $this->customLineItem;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    public function getResourceClass()
    {
        return get_class($this->resource);
    }

    public function __construct(Resource $resource, StateReference $stateReference, $item, $quantity = 1)
    {
        $this->resource = $resource;
        $this->stateReference = $stateReference;
        $this->quantity = $quantity;

        if ($item instanceof LineItem) {
            $this->lineItem = $item;
        } elseif ($item instanceof CustomLineItem) {
            $this->customLineItem = $item;
        }
    }

    public static function create(Resource $resource, StateReference $stateReference, $item, $quantity = 1)
    {
        return new static($resource, $stateReference, $item, $quantity);
    }

    public function getUpdateAction($toState)
    {
        if ($this->lineItem) {
            return OrderTransitionLineItemStateAction::ofLineItemIdQuantityAndFromToState(
                $this->lineItem->getId(),
                $this->getQuantity(),
                $this->stateReference,
                StateReference::ofKey($toState)
            );
        }

        return OrderTransitionCustomLineItemStateAction::ofCustomLineItemIdQuantityAndFromToState(
            $this->customLineItem->getId(),
            $this->getQuantity(),
            $this->stateReference,
            StateReference::ofKey($toState)
        );
    }

    /**
     * @return StateReference
     */
    public function getStateReference()
    {
        return $this->stateReference;
    }

    public function getItem()
    {
        if ($this->lineItem) {
            return $this->lineItem;
        }

        return $this->customLineItem;
    }
}
