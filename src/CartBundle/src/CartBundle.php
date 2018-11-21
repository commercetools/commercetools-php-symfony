<?php

namespace Commercetools\Symfony\CartBundle;

use Commercetools\Symfony\CartBundle\DependencyInjection\CartExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CartBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new CartExtension();
    }
}
