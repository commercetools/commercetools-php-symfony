<?php
/**
 *
 */

namespace Commercetools\Symfony\ShoppingListBundle\Tests\DependencyInjection;


use Commercetools\Symfony\ShoppingListBundle\DependencyInjection\ShoppingListExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class ShoppingListExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new ShoppingListExtension()
        ];
    }

    public function testExtensionLoads()
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('commercetools.cache.shopping_list', 'false');


        $this->assertContainerBuilderHasService('Commercetools\Symfony\ShoppingListBundle\Model\Repository\ShoppingListRepository');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager');

    }
}
