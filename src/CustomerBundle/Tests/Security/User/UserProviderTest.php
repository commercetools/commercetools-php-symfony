<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests\Security\User;


use Commercetools\Symfony\CtpBundle\Security\User\CtpUser;
use Commercetools\Symfony\CustomerBundle\Security\User\User;
use Commercetools\Symfony\CustomerBundle\Security\User\UserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProviderTest extends TestCase
{
    /**
     * @var UserProvider
     */
    private $userProvider;

    public function setUp()
    {
        $session = $this->prophesize(Session::class);
        $session->get('customer.id')->willReturn('user-1');
        $session->get('cart.id')->willReturn('cart-1');
        $session->get('cart.itemCount')->willReturn(1);

        $this->userProvider = new UserProvider($session->reveal());
    }

    public function testLoadByUsername()
    {
        $user = $this->userProvider->loadUserByUsername('foo');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('foo', $user->getUsername());
        $this->assertSame('user-1', $user->getId());
        $this->assertSame('cart-1', $user->getCartId());
        $this->assertSame(1, $user->getCartItemCount());
    }

    public function testRefreshUser()
    {
        $ctpUser = User::create('foo', 'bar', ['foobar'], '1', '2', 1);
        $user = $this->userProvider->refreshUser($ctpUser);
        $this->assertInstanceOf(CtpUser::class, $user);
        $this->assertSame($ctpUser, $user);
    }

    /**
     * @expectedException Symfony\Component\Serializer\Exception\UnsupportedException
     */
    public function testRefreshUserWithUserInterface()
    {
        $ctpUser = $this->prophesize(UserInterface::class);
        $this->userProvider->refreshUser($ctpUser->reveal());
    }

    public function testSupportsClass()
    {
        $this->assertTrue($this->userProvider->supportsClass(User::class));
        $this->assertFalse($this->userProvider->supportsClass(CtpUser::class));
    }
}



