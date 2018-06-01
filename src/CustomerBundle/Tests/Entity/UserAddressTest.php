<?php
/**
 */

namespace Commercetools\Symfony\CustomerBundle\Tests\Entity;


use Commercetools\Core\Model\Common\Address;
use Commercetools\Symfony\CustomerBundle\Entity\UserAddress;
use PHPUnit\Framework\TestCase;

class UserAddressTest extends TestCase
{
    public function testOfAddress()
    {
        $address = Address::fromArray(
            [
                'title' => 'mr',
                'salutation' => 'mr',
                'firstName' => 'Piet',
                'lastName' => 'Fiets',
                'email' => 'piet@fiets.nl',
                'streetName' => 'Akelei',
                'streetNumber' => '23',
                'building' => 'a',
                'Apartment' => 'c',
                'postalCode' => '7676dc',
                'city' => 'westerhaar',
                'country' => 'netherlands',
                'region' => 'drenthe',
                'state' => 'honkie kong',
                'POBox' => '49824',
                'AdditionalAddressInfo' => 'null',
                'AdditionalStreetInfo' => 'null',
                'phone' => '0546659948',
                'mobile' => '0620923399',
                'department' => '1',
            ]);

        $userAddress = UserAddress::ofAddress($address);
        $this->assertInstanceOf(UserAddress::class, $userAddress);
        $this->assertSame($address->getTitle(), $userAddress->getTitle());
        $this->assertSame($address->getSalutation(), $userAddress->getSalutation());
        $this->assertSame($address->getFirstName(), $userAddress->getFirstName());
        $this->assertSame($address->getLastName(), $userAddress->getLastName());
        $this->assertSame($address->getEmail(), $userAddress->getEmail());
        $this->assertSame($address->getStreetName(), $userAddress->getStreetName());
        $this->assertSame($address->getStreetNumber(), $userAddress->getStreetNumber());
        $this->assertSame($address->getBuilding(), $userAddress->getBuilding());
        $this->assertSame($address->getApartment(), $userAddress->getApartment());
        $this->assertSame($address->getPostalCode(), $userAddress->getPostalCode());
        $this->assertSame($address->getCity(), $userAddress->getCity());
        $this->assertSame($address->getCountry(), $userAddress->getCountry());
        $this->assertSame($address->getRegion(), $userAddress->getRegion());
        $this->assertSame($address->getState(), $userAddress->getState());
        $this->assertSame($address->getPOBox(), $userAddress->getPOBox());
        $this->assertSame($address->getAdditionalAddressInfo(), $userAddress->getAdditionalAddressInfo());
        $this->assertSame($address->getAdditionalStreetInfo(), $userAddress->getAdditionalStreetInfo());
        $this->assertSame($address->getPhone(), $userAddress->getPhone());
        $this->assertSame($address->getMobile(), $userAddress->getMobile());
        $this->assertSame($address->getDepartment(), $userAddress->getDepartment());
    }
}
