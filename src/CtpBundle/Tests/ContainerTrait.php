<?php
/**
 */

namespace Commercetools\Symfony\CtpBundle\Tests;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

trait ContainerTrait
{
    protected function getContainer($environment = 'test')
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
            'kernel.bundles' => [],
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => $environment,
            'kernel.root_dir' => __DIR__.'/../../', // src dir
        ]));
    }
}
