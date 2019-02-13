<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests\Security\User;

use Commercetools\Symfony\CustomerBundle\Security\User\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class UserTest extends TestCase
{
    public function testCreateUser()
    {
        $user = User::create('alice', 'foo', ['bar'], 'id-1', 'cart-1', 1);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(['bar'], $user->getRoles());
        $this->assertSame('foo', $user->getPassword());
        $this->assertSame('alice', $user->getUsername());
        $this->assertSame('id-1', $user->getId());
        $this->assertSame('cart-1', $user->getCartId());
        $this->assertSame(1, $user->getCartItemCount());
    }

    public function testIsEqualTo()
    {
        $user = User::create('alice', 'foo', ['bar'], 'id-1', 'cart-1', 1);
        $user2 = User::create('alice', 'foobar', ['barfoo'], 'id-1', 'cart-2', 2);
        $user3 = User::create('bob', 'foo', ['bar'], 'id-1', 'cart-1', 1);
        $user4 = User::create('alice', 'foo', ['bar'], 'id-3', 'cart-1', 1);

        $this->assertTrue($user->isEqualTo($user2));
        $this->assertFalse($user->isEqualTo($user3));
        $this->assertFalse($user->isEqualTo($user4));

        $fooUser = $this->prophesize(UserInterface::class);
        $this->assertFalse($user->isEqualTo($fooUser->reveal()));
    }

    public function testSetters()
    {
        $user = User::create('alice', 'foo', ['bar'], 'id-1', 'cart-1', 1);
        $user->setId('id-2');
        $this->assertSame('id-2', $user->getId());
        $user->setCartId('cart-2');
        $this->assertSame('cart-2', $user->getCartId());
        $user->setCartItemCount(2);
        $this->assertSame(2, $user->getCartItemCount());
        $user->setDefaultShippingAddress('foo');
        $this->assertSame('foo', $user->getDefaultShippingAddress());
        $user->setShippingAddresses('bar');
        $this->assertSame('bar', $user->getShippingAddresses());
    }
}
