<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Model\Form\Type;

use Commercetools\Symfony\ExampleBundle\Entity\UserAddress;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddressType;
use Symfony\Component\Form\Test\TypeTestCase;

class AddressTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $formData = [
            'title' => 'mr',
            'salutation' => 'mr',
            'firstName' => 'foo',
            'lastName' => 'bar',
            'email' => 'user@localhost',
            'streetName' => 'Streetstr',
            'streetNumber' => '23',
            'building' => 'a',
            'apartment' => 'c',
            'postalCode' => '7676dc',
            'city' => 'berlin',
            'country' => 'DE',
            'region' => 'neukoln',
            'state' => 'state1',
            'pOBox' => '49824',
            'additionalAddressInfo' => 'null',
            'additionalStreetInfo' => 'null',
            'phone' => '0546659948',
            'mobile' => '0620923399',
            'department' => '1',
        ];

        $userAddress = new UserAddress();

        $form = $this->factory->create(AddressType::class, $userAddress);

        $expectedUserAddress = new UserAddress();
        $expectedUserAddress
            ->setTitle('mr')
            ->setFirstName('foo')
            ->setLastName('bar')
            ->setEmail('user@localhost')
            ->setStreetName('Streetstr')
            ->setStreetNumber('23')
            ->setBuilding('a')
            ->setApartment('c')
            ->setPostalCode('7676dc')
            ->setCity('berlin')
            ->setCountry('DE')
            ->setRegion('neukoln')
            ->setState('state1')
            ->setPOBox('49824')
            ->setAdditionalAddressInfo('null')
            ->setAdditionalStreetInfo('null')
            ->setPhone('0546659948')
            ->setMobile('0620923399')
            ->setDepartment('1')
        ;

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedUserAddress, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
