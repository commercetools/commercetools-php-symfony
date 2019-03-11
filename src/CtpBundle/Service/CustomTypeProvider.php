<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Core\Model\Type\Type;
use Commercetools\Core\Model\Type\TypeCollection;
use Commercetools\Core\Model\Type\TypeReference;

class CustomTypeProvider
{
    /** @var TypeCollection */
    private $customTypes;

    /**
     * CustomTypeProvider constructor.
     * @param TypeCollection $customTypes
     */
    public function __construct(TypeCollection $customTypes)
    {
        $this->customTypes = $customTypes;
    }

    /**
     * @param string $customTypeKey
     * @return Type|null
     */
    public function getType($customTypeKey)
    {
        return $this->customTypes->getByKey($customTypeKey);
    }

    /**
     * @param $customTypeKey
     * @return TypeReference|null
     */
    public function getTypeReference($customTypeKey)
    {
        if (!is_null($this->getType($customTypeKey))) {
            return $this->getType($customTypeKey)->getReference();
        }

        return null;
    }
}
