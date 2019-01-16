<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Core\Model\Type\TypeCollection;
use Commercetools\Core\Model\Type\TypeReference;

class CustomTypeProvider
{
    public function __construct(TypeCollection $customTypes)
    {
        foreach ($customTypes as $customType) {
            $key = $customType->getKey();
            $this->$key = $customType;
        }
    }

    public function getType($customTypeKey)
    {
        return $this->$customTypeKey ?? null;
    }

    public function getTypeReference($customTypeKey)
    {
        if (!is_null($this->$customTypeKey)) {
            return TypeReference::ofId($this->$customTypeKey->getId());
        }

        return null;
    }
}
