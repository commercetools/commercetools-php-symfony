<?php
/**
 *
 */

namespace Commercetools\Symfony\CatalogBundle\Tests\DependencyInjection;


use Commercetools\Symfony\CatalogBundle\DependencyInjection\CatalogExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class CatalogExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new CatalogExtension()
        ];
    }

    public function testExtensionLoads()
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('commercetools.cache.catalog', 'false');

        $this->assertContainerBuilderHasService('Commercetools\Symfony\CatalogBundle\Model\Repository\CatalogRepository');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\CatalogBundle\Manager\CatalogManager');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\CatalogBundle\Model\Search');
    }
}
