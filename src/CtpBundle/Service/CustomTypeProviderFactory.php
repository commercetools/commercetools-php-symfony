<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Core\Model\Type\TypeCollection;

class CustomTypeProviderFactory
{
    /**
     * @param array $customTypes
     * @return CustomTypeProvider
     */
    public function build(array $customTypes): CustomTypeProvider
    {
        return new CustomTypeProvider(TypeCollection::fromArray($customTypes));
    }
}
