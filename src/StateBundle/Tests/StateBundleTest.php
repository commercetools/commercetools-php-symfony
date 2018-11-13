<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests;


use Commercetools\Symfony\StateBundle\DependencyInjection\StateExtension;
use Commercetools\Symfony\StateBundle\StateBundle;
use PHPUnit\Framework\TestCase;


class StateBundleTest extends TestCase
{
    public function testGetContainerExtension()
    {
        $stateBundle = new StateBundle();
        $this->assertInstanceOf(StateExtension::class, $stateBundle->getContainerExtension());
    }
}
