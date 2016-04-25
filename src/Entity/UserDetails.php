<?php

namespace Commercetools\Symfony\CtpBundle\Entity;

use Commercetools\Core\Model\Customer\Customer;

/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */
class UserDetails
{
    private $firstName;
    private $lastName;
    private $email;
    private $password;
    private $currentPassword;

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrentPassword()
    {
        return $this->currentPassword;
    }

    /**
     * @param mixed $currentPassword
     * @return $this
     */
    public function setCurrentPassword($currentPassword)
    {
        $this->currentPassword = $currentPassword;

        return $this;
    }



    public static function ofCustomer(Customer $customer)
    {
        $userDetails = new static();
        $userDetails->setFirstName($customer->getFirstName());
        $userDetails->setLastName($customer->getLastName());
        $userDetails->setEmail($customer->getEmail());

        return $userDetails;
    }
}