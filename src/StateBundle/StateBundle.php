<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle;


use Commercetools\Symfony\StateBundle\DependencyInjection\StateExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class StateBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new StateExtension();
    }
}

