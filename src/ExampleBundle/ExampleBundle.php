<?php

namespace Commercetools\Symfony\ExampleBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ExampleBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ProfilerControllerPass());
    }

}
