<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Core\Model\Type\TypeCollection;

class CustomTypeProvider
{
    /** @var TypeCollection */
    private $customTypes;

    public function __construct(TypeCollection $customTypes)
    {
        $this->customTypes = $customTypes;
    }

    public function getType($customTypeKey)
    {
        return $this->customTypes->getByKey($customTypeKey);
    }

    public function getTypeReference($customTypeKey)
    {
        if (!is_null($this->getType($customTypeKey))) {
            return $this->getType($customTypeKey)->getReference();
        }

        return null;
    }
}
