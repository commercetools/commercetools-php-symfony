<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\EventListener;

use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CustomerBundle\Model\Repository\CustomerRepository;
use Commercetools\Symfony\CustomerBundle\Security\User\User;
use Commercetools\Symfony\ExampleBundle\EventListener\AuthenticationSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

class AuthenticationSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $session = $this->prophesize(SessionInterface::class);
        $subscriber = new AuthenticationSubscriber($session->reveal());

        $this->assertArrayHasKey(AuthenticationEvents::AUTHENTICATION_SUCCESS, $subscriber->getSubscribedEvents());
        $this->assertArrayHasKey(AuthenticationEvents::AUTHENTICATION_FAILURE, $subscriber->getSubscribedEvents());
    }

    public function testOnAuthenticationSuccessWithCart()
    {
        $session = $this->prophesize(SessionInterface::class);
        $event = $this->prophesize(AuthenticationEvent::class);
        $token = $this->prophesize(TokenInterface::class);
        $user = $this->prophesize(User::class);

        $user->getId()->willReturn('user-1')->shouldBeCalledOnce();
        $user->getCartItemCount()->willReturn(3)->shouldBeCalledTimes(2);
        $user->getCartId()->willReturn('cart-1')->shouldBeCalledTimes(2);

        $session->set(CustomerRepository::CUSTOMER_ID, 'user-1')->shouldBeCalledOnce();
        $session->set(CartRepository::CART_ITEM_COUNT, 3)->shouldBeCalledOnce();
        $session->set(CartRepository::CART_ID, 'cart-1')->shouldBeCalledOnce();

        $token->getUser()->will(function ($args) use ($user) {
            return $user->reveal();
        })->shouldBeCalledOnce();

        $event->getAuthenticationToken()->will(function ($args) use ($token) {
            return $token->reveal();
        })->shouldBeCalledOnce();

        $subscriber = new AuthenticationSubscriber($session->reveal());
        $subscriber->onAuthenticationSuccess($event->reveal());
    }

    public function testOnAuthenticationSuccessWithoutCart()
    {
        $session = $this->prophesize(SessionInterface::class);
        $event = $this->prophesize(AuthenticationEvent::class);
        $token = $this->prophesize(TokenInterface::class);
        $user = $this->prophesize(User::class);

        $user->getId()->willReturn('user-1')->shouldBeCalledOnce();
        $user->getCartItemCount()->willReturn(null)->shouldBeCalledOnce();
        $user->getCartId()->willReturn(null)->shouldBeCalledOnce();

        $session->set(CustomerRepository::CUSTOMER_ID, 'user-1')->shouldBeCalledOnce();
        $session->remove(CartRepository::CART_ITEM_COUNT)->shouldBeCalledTimes(2);
        $session->remove(CartRepository::CART_ID)->shouldBeCalledOnce();

        $token->getUser()->will(function ($args) use ($user) {
            return $user->reveal();
        })->shouldBeCalledOnce();

        $event->getAuthenticationToken()->will(function ($args) use ($token) {
            return $token->reveal();
        })->shouldBeCalledOnce();

        $subscriber = new AuthenticationSubscriber($session->reveal());
        $subscriber->onAuthenticationSuccess($event->reveal());
    }

    public function testOnAuthenticationSuccessForOtherUser()
    {
        $session = $this->prophesize(SessionInterface::class);
        $event = $this->prophesize(AuthenticationEvent::class);
        $token = $this->prophesize(TokenInterface::class);

        $session->get(CustomerRepository::CUSTOMER_ID)
            ->willReturn('user-1')->shouldBeCalledOnce();
        $session->remove(CustomerRepository::CUSTOMER_ID)->shouldBeCalledOnce();
        $session->remove(CartRepository::CART_ITEM_COUNT)->shouldBeCalledOnce();
        $session->remove(CartRepository::CART_ID)->shouldBeCalledOnce();

        $token->getUser()->willReturn(null)->shouldBeCalledOnce();

        $event->getAuthenticationToken()->will(function ($args) use ($token) {
            return $token->reveal();
        })->shouldBeCalledOnce();

        $subscriber = new AuthenticationSubscriber($session->reveal());
        $subscriber->onAuthenticationSuccess($event->reveal());
    }
}
