<?php

namespace Commercetools\Symfony\CustomerBundle;

use Commercetools\Symfony\CustomerBundle\DependencyInjection\CustomerExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CustomerBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new CustomerExtension();
    }
}
