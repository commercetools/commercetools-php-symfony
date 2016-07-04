<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Controller;

use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Request\Customers\Command\CustomerChangeAddressAction;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testOfeditAddress()
    {
        $addressId = '';
        $addressChange = CustomerChangeAddressAction::ofAddressIdAndAddress($addressId);
        $address = Address::fromArray([
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

//        $userAddress = User
    }
}