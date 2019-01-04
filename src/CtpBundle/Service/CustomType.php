<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Type\TypeReference;

class CustomType
{
    public function __construct($customTypes)
    {
        foreach ($customTypes as $customTypeKey => $customTypeValue) {
            $this->$customTypeKey = $customTypeValue;
        }
    }

    public function get($customTypeKey)
    {
        if (!is_null($this->$customTypeKey)) {
            return $this->getTypeReference($customTypeKey);
        }

        return null;
    }

    public function getTypeReference($customType)
    {
        if (!is_null($this->$customType['id'])) {
            return TypeReference::ofId($this->$customType['id']);
        } else if (!is_null($this->$customType['key'])) {
            return TypeReference::ofKey($this->$customType['key']);
        } else {
            throw new InvalidArgumentException('Cannot create type reference for ' . $customType);
        }
    }
}
