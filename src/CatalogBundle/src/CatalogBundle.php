<?php

namespace Commercetools\Symfony\CatalogBundle;

use Commercetools\Symfony\CatalogBundle\DependencyInjection\CatalogExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CatalogBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new CatalogExtension();
    }
}
