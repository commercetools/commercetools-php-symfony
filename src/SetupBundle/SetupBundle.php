<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle;


use Commercetools\Symfony\SetupBundle\DependencyInjection\SetupExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SetupBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new SetupExtension();
    }
}

