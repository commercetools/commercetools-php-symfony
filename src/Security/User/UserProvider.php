<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Security\User;

use Commercetools\Symfony\CtpBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CtpBundle\Model\Repository\CustomerRepository;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Serializer\Exception\UnsupportedException;

class UserProvider implements UserProviderInterface
{
    private $session;

    /**
     * UserProvider constructor.
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function loadUserByUsername($username)
    {
        $id = $this->session->get(CustomerRepository::CUSTOMER_ID);
        $cartId = $this->session->get(CartRepository::CART_ID);
        $cartItemCount = $this->session->get(CartRepository::CART_ITEM_COUNT);

        return new User($username, '', ['ROLE_USER'], $id, $cartId, $cartItemCount);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $user;
    }

    public function supportsClass($class)
    {
        return $class === 'Commercetools\Symfony\CtpBundle\Security\User\User';
    }
}
