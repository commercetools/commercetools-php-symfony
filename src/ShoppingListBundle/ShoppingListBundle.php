<?php

namespace Commercetools\Symfony\ShoppingListBundle;

use Commercetools\Symfony\ShoppingListBundle\DependencyInjection\ShoppingListExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ShoppingListBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new ShoppingListExtension();
    }
}
