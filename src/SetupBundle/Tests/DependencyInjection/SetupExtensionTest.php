<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Tests\DependencyInjection;

use Commercetools\Symfony\SetupBundle\DependencyInjection\SetupExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class SetupExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new SetupExtension()
        ];
    }

    public function testExtensionLoads()
    {
        $this->load();

        $this->assertContainerBuilderHasService('Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\SetupBundle\Command\CommercetoolsProjectInfoCommand');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\SetupBundle\Command\CommercetoolsProjectApplyConfigurationCommand');
    }
}
