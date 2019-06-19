<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Entity;

use Commercetools\Core\Model\Common\Address;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class UserAddress
{
    private $title;
    private $salutation;
    private $firstName;
    private $lastName;
    private $email;
    private $company;
    private $streetName;
    private $streetNumber;
    private $building;
    private $apartment;
    private $department;
    private $postalCode;
    private $city;
    private $country;
    private $region;
    private $state;
    private $pOBox;
    private $additionalAddressInfo;
    private $additionalStreetInfo;
    private $phone;
    private $mobile;


    /**
     * @return mixed
     */
    public function getAdditionalStreetInfo()
    {
        return $this->additionalStreetInfo;
    }

    /**
     * @param mixed $additionalStreetInfo
     * @return $this
     */
    public function setAdditionalStreetInfo($additionalStreetInfo)
    {
        $this->additionalStreetInfo = $additionalStreetInfo;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getAdditionalAddressInfo()
    {
        return $this->additionalAddressInfo;
    }

    /**
     * @param mixed $additionalAddressInfo
     * @return $this
     */
    public function setAdditionalAddressInfo($additionalAddressInfo)
    {
        $this->additionalAddressInfo = $additionalAddressInfo;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * @param mixed $salutation
     * @return $this
     */
    public function setSalutation($salutation)
    {
        $this->salutation = $salutation;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @param mixed $department
     * @return UserAddress
     */
    public function setDepartment($department)
    {
        $this->department = $department;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     * @return $this
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }


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
    public function getStreetName()
    {
        return $this->streetName;
    }

    /**
     * @param mixed $streetName
     * @return $this
     */
    public function setStreetName($streetName)
    {
        $this->streetName = $streetName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * @param mixed $streetNumber
     * @return $this
     */
    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBuilding()
    {
        return $this->building;
    }

    /**
     * @param mixed $building
     * @return $this
     */
    public function setBuilding($building)
    {
        $this->building = $building;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getApartment()
    {
        return $this->apartment;
    }

    /**
     * @param mixed $apartment
     * @return $this
     */
    public function setApartment($apartment)
    {
        $this->apartment = $apartment;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param mixed $postalCode
     * @return $this
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     * @return $this
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param mixed $region
     * @return $this
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPOBox()
    {
        return $this->pOBox;
    }

    /**
     * @param mixed $pOBox
     * @return $this
     */
    public function setPOBox($pOBox)
    {
        $this->pOBox = $pOBox;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param mixed $mobile
     * @return $this
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }



    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('firstName', new NotBlank());
        $metadata->addPropertyConstraint('firstName', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('lastName', new NotBlank());
        $metadata->addPropertyConstraint('lastName', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('streetName', new NotBlank());
        $metadata->addPropertyConstraint('streetName', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('streetNumber', new NotBlank());
        $metadata->addPropertyConstraint('streetNumber', new Length(['min' => 1, 'max' => 255]));

        $metadata->addPropertyConstraint('postalCode', new NotBlank());
        $metadata->addPropertyConstraint('postalCode', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('city', new NotBlank());
        $metadata->addPropertyConstraint('city', new Length(['min' => 3, 'max' => 255]));

//        $metadata->addPropertyConstraint('region', new NotBlank());
//        $metadata->addPropertyConstraint('region', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('country', new NotBlank());
        $metadata->addPropertyConstraint('country', new Length(['min' => 2, 'max' => 2]));

//        $metadata->addPropertyConstraint('phone', new NotBlank());
//        $metadata->addPropertyConstraint('phone', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('email', new NotBlank());
        $metadata->addPropertyConstraint('email', new Email());
        $metadata->addPropertyConstraint('email', new Length(['min' => 5, 'max' => 255]));
    }

    /**
     * @param Address $address
     * @return static
     */
    public static function ofAddress(Address $address)
    {
        $userAddress = new static();
        $userAddress->setTitle($address->getTitle());
        $userAddress->setSalutation($address->getSalutation());
        $userAddress->setCompany($address->getCompany());
        $userAddress->setFirstName($address->getFirstName());
        $userAddress->setLastName($address->getLastName());
        $userAddress->setEmail($address->getEmail());
        $userAddress->setStreetName($address->getStreetName());
        $userAddress->setStreetNumber($address->getStreetNumber());
        $userAddress->setBuilding($address->getBuilding());
        $userAddress->setApartment($address->getApartment());
        $userAddress->setPostalCode($address->getPostalCode());
        $userAddress->setCity($address->getCity());
        $userAddress->setCountry($address->getCountry());
        $userAddress->setRegion($address->getRegion());
        $userAddress->setState($address->getState());
        $userAddress->setPOBox($address->getPOBox());
        $userAddress->setAdditionalAddressInfo($address->getAdditionalAddressInfo());
        $userAddress->setAdditionalStreetInfo($address->getAdditionalStreetInfo());
        $userAddress->setPhone($address->getPhone());
        $userAddress->setMobile($address->getMobile());
        $userAddress->setDepartment($address->getDepartment());
        $userAddress->setPostalCode($address->getPostalCode());

        return $userAddress;
    }

    /**
     * @return Address
     */
    public function toCTPAddress()
    {
        $newAddress = Address::of();
        $newAddress->setTitle($this->getTitle());
        $newAddress->setSalutation($this->getSalutation());
        $newAddress->setCompany($this->getCompany());
        $newAddress->setFirstName($this->getFirstName());
        $newAddress->setLastName($this->getLastName());
        $newAddress->setEmail($this->getEmail());
        $newAddress->setStreetName($this->getStreetName());
        $newAddress->setStreetNumber($this->getStreetNumber());
        $newAddress->setBuilding($this->getBuilding());
        $newAddress->setApartment($this->getApartment());
        $newAddress->setPostalCode($this->getPostalCode());
        $newAddress->setCity($this->getCity());
        $newAddress->setCountry($this->getCountry());
        $newAddress->setRegion($this->getRegion());
        $newAddress->setState($this->getState());
        $newAddress->setPOBox($this->getPOBox());
        $newAddress->setAdditionalAddressInfo($this->getAdditionalAddressInfo());
        $newAddress->setAdditionalStreetInfo($this->getAdditionalStreetInfo());
        $newAddress->setPhone($this->getPhone());
        $newAddress->setMobile($this->getMobile());
        $newAddress->setDepartment($this->getDepartment());
        $newAddress->setPostalCode($this->getPostalCode());

        return $newAddress;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}
