<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\EventListener;

use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CtpBundle\Model\Repository\CustomerRepository;
use Commercetools\Symfony\CtpBundle\Security\User\CtpUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

class AuthenticationListener implements EventSubscriberInterface
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return [
            AuthenticationEvents::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure'
        ];
    }
    public function onAuthenticationSuccess(AuthenticationEvent $event)
    {
        $token = $event->getAuthenticationToken();
        $user = $token->getUser();

        if ($user instanceof CtpUser) {
            $this->session->set(CustomerRepository::CUSTOMER_ID, $user->getId());

            if (!is_null($user->getCartItemCount())) {
                $this->session->set(CartRepository::CART_ITEM_COUNT, $user->getCartItemCount());
            } else {
                $this->session->remove(CartRepository::CART_ITEM_COUNT);
            }

            if (!is_null($user->getCartId())) {
                $this->session->set(CartRepository::CART_ID, $user->getCartId());
            } else {
                $this->session->remove(CartRepository::CART_ID);
                $this->session->remove(CartRepository::CART_ITEM_COUNT);
            }
        }
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event )
    {

    }
}
