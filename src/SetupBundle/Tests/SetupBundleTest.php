<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Tests;


use Commercetools\Symfony\SetupBundle\DependencyInjection\SetupExtension;
use Commercetools\Symfony\SetupBundle\SetupBundle;
use PHPUnit\Framework\TestCase;


class SetupBundleTest extends TestCase
{
    public function testGetContainerExtension()
    {
        $setupBundle = new SetupBundle();
        $this->assertInstanceOf(SetupExtension::class, $setupBundle->getContainerExtension());
    }
}
