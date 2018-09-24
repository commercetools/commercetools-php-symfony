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

        $user->setEmail('user2@email.com');
        $this->assertSame('user2@email.com', $user->getEmail());

        $user->setNewPassword('foo');
        $this->assertSame('foo', $user->getNewPassword());

        $user->setFirstName('first')->setLastName('last');
        $this->assertSame('first', $user->getFirstName());
        $this->assertSame('last', $user->getLastName());
    }
}
