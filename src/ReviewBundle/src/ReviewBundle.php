<?php

namespace Commercetools\Symfony\ReviewBundle;

use Commercetools\Symfony\ReviewBundle\DependencyInjection\ReviewExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ReviewBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new ReviewExtension();
    }
}
