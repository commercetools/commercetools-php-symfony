<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\Cart\CustomLineItem;
use Commercetools\Core\Model\Cart\LineItem;
use Commercetools\Core\Model\Order\ItemState;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Model\Review\Review;

class StateType
{
    const TYPES = [
        'OrderState' => [Order::class],
        'LineItemState' => [
            ItemState::class,
            LineItem::class,
            CustomLineItem::class
        ],
        'ProductState' => [Product::class],
        'ReviewState' => [Review::class],
        'PaymentState' => [Payment::class]
    ];
}
