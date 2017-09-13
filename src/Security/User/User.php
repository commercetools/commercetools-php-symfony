<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Security\User;


use Symfony\Component\Security\Core\User\UserInterface;

class User implements CtpUser
{
    private $username;
    private $password;
    private $roles;
    private $id;
    private $cartId;
    private $cartItemCount;

    public function __construct($username, $password, array $roles, $id, $cartId, $cartItemCount)
    {
        $this->username = $username;
        $this->password = $password;
        $this->roles = $roles;
        $this->id= $id;
        $this->cartItemCount= $cartItemCount;
        $this->cartId= $cartId;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        // not needed;
    }

    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getCartId()
    {
        return $this->cartId;
    }

    /**
     * @param mixed $cartId
     */
    public function setCartId($cartId)
    {
        $this->cartId = $cartId;
    }

    /**
     * @return mixed
     */
    public function getCartItemCount()
    {
        return $this->cartItemCount;
    }

    /**
     * @param mixed $cartItemCount
     */
    public function setCartItemCount($cartItemCount)
    {
        $this->cartItemCount = $cartItemCount;
    }

    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof CtpUser) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        if ($this->id !== $user->getId()) {
            return false;
        }

        return true;
    }

    public static function create($username, $password, array $roles, $id, $cartId, $cartItemCount)
    {
        return new static($username, $password, $roles, $id, $cartId, $cartItemCount);
    }
}
