<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;

use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Model\Review\Review;

class StateType
{
    const TYPES = [
        'OrderState' => [Order::class],
        'LineItemState' => [ItemStateWrapper::class],
        'ProductState' => [Product::class],
        'ReviewState' => [Review::class],
        'PaymentState' => [Payment::class]
    ];
}
