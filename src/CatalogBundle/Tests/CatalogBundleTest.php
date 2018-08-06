<?php
/**
 *
 */

namespace Commercetools\Symfony\CatalogBundle\Tests;

use Commercetools\Symfony\CatalogBundle\DependencyInjection\CatalogExtension;
use Commercetools\Symfony\CatalogBundle\CatalogBundle;
use PHPUnit\Framework\TestCase;

class CatalogBundleTest extends TestCase
{
    public function testGetContainerExtension()
    {
        $catalogBundle = new CatalogBundle();
        $this->assertInstanceOf(CatalogExtension::class, $catalogBundle->getContainerExtension());
    }
}
