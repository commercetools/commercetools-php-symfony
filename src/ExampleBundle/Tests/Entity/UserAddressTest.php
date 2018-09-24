<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Entity;


use Commercetools\Core\Model\Common\Address;
use Commercetools\Symfony\ExampleBundle\Entity\UserAddress;
use PHPUnit\Framework\TestCase;

class UserAddressTest extends TestCase
{
    private $data = [
        'title' => 'mr',
        'salutation' => 'mr',
        'firstName' => 'Piet',
        'lastName' => 'Fiets',
        'email' => 'piet@fiets.nl',
        'streetName' => 'Akelei',
        'streetNumber' => '23',
        'building' => 'a',
        'apartment' => 'c',
        'postalCode' => '7676dc',
        'city' => 'westerhaar',
        'country' => 'netherlands',
        'region' => 'drenthe',
        'state' => 'honkie kong',
        'pOBox' => '49824',
        'additionalAddressInfo' => 'null',
        'additionalStreetInfo' => 'null',
        'phone' => '0546659948',
        'mobile' => '0620923399',
        'department' => '1',
        'company' => 'CT'
    ];

    public function testOfAddress()
    {
        $address = Address::fromArray($this->data);
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
        $this->assertSame($address->getCompany(), $userAddress->getCompany());
    }

    public function testToCTPAddress()
    {
        $address = Address::fromArray($this->data);
        $userAddress = UserAddress::ofAddress($address);
        $anotherAddress = $userAddress->toCTPAddress();

        $this->assertInstanceOf(Address::class, $anotherAddress);
        $this->assertSame($address->getTitle(), $anotherAddress->getTitle());
        $this->assertSame($address->getSalutation(), $anotherAddress->getSalutation());
        $this->assertSame($address->getFirstName(), $anotherAddress->getFirstName());
        $this->assertSame($address->getLastName(), $anotherAddress->getLastName());
        $this->assertSame($address->getEmail(), $anotherAddress->getEmail());
        $this->assertSame($address->getStreetName(), $anotherAddress->getStreetName());
        $this->assertSame($address->getStreetNumber(), $anotherAddress->getStreetNumber());
        $this->assertSame($address->getBuilding(), $anotherAddress->getBuilding());
        $this->assertSame($address->getApartment(), $anotherAddress->getApartment());
        $this->assertSame($address->getPostalCode(), $anotherAddress->getPostalCode());
        $this->assertSame($address->getCity(), $anotherAddress->getCity());
        $this->assertSame($address->getCountry(), $anotherAddress->getCountry());
        $this->assertSame($address->getRegion(), $anotherAddress->getRegion());
        $this->assertSame($address->getState(), $anotherAddress->getState());
        $this->assertSame($address->getPOBox(), $anotherAddress->getPOBox());
        $this->assertSame($address->getAdditionalAddressInfo(), $anotherAddress->getAdditionalAddressInfo());
        $this->assertSame($address->getAdditionalStreetInfo(), $anotherAddress->getAdditionalStreetInfo());
        $this->assertSame($address->getPhone(), $anotherAddress->getPhone());
        $this->assertSame($address->getMobile(), $anotherAddress->getMobile());
        $this->assertSame($address->getDepartment(), $anotherAddress->getDepartment());
        $this->assertSame($address->getCompany(), $anotherAddress->getCompany());
    }

    public function testToArray()
    {
        $address = Address::fromArray($this->data);
        $userAddress = UserAddress::ofAddress($address);
        $arrayAddress = $userAddress->toArray();

        $this->assertEquals($this->data, $arrayAddress);
    }
}
