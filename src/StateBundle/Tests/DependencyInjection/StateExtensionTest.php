<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\DependencyInjection;

use Commercetools\Symfony\StateBundle\DependencyInjection\StateExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class StateExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new StateExtension()
        ];
    }

    public function testExtensionLoads()
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('commercetools.cache.states', 'false');

        $this->assertContainerBuilderHasService('Commercetools\Symfony\StateBundle\Model\Repository\StateRepository');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\StateBundle\Command\CommercetoolsStateCommand');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\StateBundle\Command\CommercetoolsWorkflowCommand');
    }
}
