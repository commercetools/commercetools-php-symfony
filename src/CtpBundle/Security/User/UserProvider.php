<?php
/**
 */

namespace Commercetools\Symfony\CtpBundle\Security\User;

use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CustomerBundle\Model\Repository\CustomerRepository;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Serializer\Exception\UnsupportedException;

class UserProvider implements UserProviderInterface
{
    private $session;
    private $userClass;

    /**
     * UserProvider constructor.
     */
    public function __construct(Session $session, $userClass = User::class)
    {
        $this->session = $session;
        $this->userClass = $userClass;
    }

    public function loadUserByUsername($username)
    {
        $id = $this->session->get(CustomerRepository::CUSTOMER_ID);
        $cartId = $this->session->get(CartRepository::CART_ID);
        $cartItemCount = $this->session->get(CartRepository::CART_ITEM_COUNT);

        $userClass = $this->userClass;
        return $userClass::create($username, '', ['ROLE_USER'], $id, $cartId, $cartItemCount);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof CtpUser) {
            throw new UnsupportedException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $user;
    }

    public function supportsClass($class)
    {
        return in_array(CtpUser::class, class_implements($class));
    }
}
