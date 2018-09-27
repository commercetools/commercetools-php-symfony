<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Entity;


use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Symfony\ExampleBundle\Entity\UserDetails;
use PHPUnit\Framework\TestCase;

class UserDetailsTest extends TestCase
{
    public function testOfCustomer()
    {
        $customer = Customer::of()
            ->setFirstName('foo')
            ->setLastName('bar')
            ->setEmail('user1@email.com');

        $user = UserDetails::ofCustomer($customer);

        $this->assertInstanceOf(UserDetails::class, $user);
        $this->assertSame($customer->getFirstName(), $user->getFirstName());
        $this->assertSame($customer->getLastName(), $user->getLastName());
        $this->assertSame($customer->getEmail(), $user->getEmail());
    }

    public function testSetNewPassword()
    {
        $user = new UserDetails();
        $user->setNewPassword('foo');
        $this->assertSame('foo', $user->getNewPassword());
    }

    public function testSetPassword()
    {
        $user = new UserDetails();
        $user->setPassword('foo');
        $this->assertSame('foo', $user->getPassword());
    }

    public function testSetCurrentPassword()
    {
        $user = new UserDetails();
        $user->setCurrentPassword('foo');
        $this->assertSame('foo', $user->getCurrentPassword());
    }

    public function testSetFirstName()
    {
        $user = new UserDetails();
        $user->setFirstName('foo');
        $this->assertSame('foo', $user->getFirstName());
    }

    public function testSetLastName()
    {
        $user = new UserDetails();
        $user->setLastName('foo');
        $this->assertSame('foo', $user->getLastName());
    }

    public function testSetEmail()
    {
        $user = new UserDetails();
        $user->setEmail('user2@email.com');
        $this->assertSame('user2@email.com', $user->getEmail());
    }
}
