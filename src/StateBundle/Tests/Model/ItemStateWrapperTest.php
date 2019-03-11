<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Model;

use Commercetools\Core\Model\Cart\CustomLineItem;
use Commercetools\Core\Model\Cart\LineItem;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Orders\Command\OrderTransitionCustomLineItemStateAction;
use Commercetools\Core\Request\Orders\Command\OrderTransitionLineItemStateAction;
use Commercetools\Symfony\StateBundle\Model\ItemStateWrapper;
use PHPUnit\Framework\TestCase;

class ItemStateWrapperTest extends TestCase
{
    public function testCreate()
    {
        $resource = Order::of();
        $stateReference = StateReference::of();
        $item = LineItem::of()->setId('lineItem-1');

        $itemStateWrapper = ItemStateWrapper::create($resource, $stateReference, $item);

        $this->assertInstanceOf(LineItem::class, $itemStateWrapper->getLineItem());
        $this->assertNull($itemStateWrapper->getCustomLineItem());
        $this->assertSame(1, $itemStateWrapper->getQuantity());
        $this->assertInstanceOf(Order::class, $itemStateWrapper->getResource());
        $this->assertSame(Order::class, $itemStateWrapper->getResourceClass());
        $this->assertInstanceOf(StateReference::class, $itemStateWrapper->getStateReference());
        $this->assertInstanceOf(LineItem::class, $itemStateWrapper->getItem());

        $updateAction = $itemStateWrapper->getUpdateAction('foo');
        $this->assertInstanceOf(OrderTransitionLineItemStateAction::class, $updateAction);
        $this->assertSame('lineItem-1', $updateAction->getLineItemId());
        $this->assertSame('foo', $updateAction->getToState()->getKey());
    }

    public function testCreateForCustomLineItem()
    {
        $resource = Order::of();
        $stateReference = StateReference::of();
        $item = CustomLineItem::of()->setId('customLineItem-1');

        $itemStateWrapper = ItemStateWrapper::create($resource, $stateReference, $item);

        $this->assertInstanceOf(CustomLineItem::class, $itemStateWrapper->getCustomLineItem());
        $this->assertNull($itemStateWrapper->getLineItem());
        $this->assertSame(1, $itemStateWrapper->getQuantity());
        $this->assertInstanceOf(Order::class, $itemStateWrapper->getResource());
        $this->assertSame(Order::class, $itemStateWrapper->getResourceClass());
        $this->assertInstanceOf(StateReference::class, $itemStateWrapper->getStateReference());
        $this->assertInstanceOf(CustomLineItem::class, $itemStateWrapper->getItem());

        $updateAction = $itemStateWrapper->getUpdateAction('foo');
        $this->assertInstanceOf(OrderTransitionCustomLineItemStateAction::class, $updateAction);
        $this->assertSame('customLineItem-1', $updateAction->getCustomLineItemId());
        $this->assertSame('foo', $updateAction->getToState()->getKey());
    }
}
