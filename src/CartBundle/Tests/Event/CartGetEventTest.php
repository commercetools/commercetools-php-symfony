<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Event;


use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Symfony\CartBundle\Event\CartGetEvent;
use PHPUnit\Framework\TestCase;

class CartGetEventTest extends TestCase
{
    public function testCartGetEvent()
    {
        $getEvent = new CartGetEvent(Cart::of());
        $this->assertInstanceOf(Cart::class, $getEvent->getCart());
    }
}
