<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Event;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Symfony\CartBundle\Event\CartPostCreateEvent;
use PHPUnit\Framework\TestCase;

class CartPostCreateEventTest extends TestCase
{
    public function testCartPostCreateEvent()
    {
        $postCreateEvent = new CartPostCreateEvent(Cart::of());
        $this->assertInstanceOf(Cart::class, $postCreateEvent->getCart());
    }
}
