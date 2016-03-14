<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Security\User;

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
        $id = $this->session->get('customer.id');

        return new User($username, '', ['ROLE_USER'], $id);
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