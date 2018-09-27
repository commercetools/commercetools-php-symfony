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

        $this->assertContainerBuilderHasService('Commercetools\Symfony\StateBundle\Cache\StateKeyResolver');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\StateBundle\Cache\StateWarmer');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\StateBundle\Extension\ItemStateExtension');

        $this->assertContainerBuilderHasService('Commercetools\Symfony\StateBundle\Model\CtpMarkingStore\CtpMarkingStore');

        $this->assertContainerBuilderHasService('Commercetools\Symfony\StateBundle\Model\TransitionHandler\LineItemStateTransitionHandler');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\StateBundle\Model\TransitionHandler\OrderStateTransitionHandler');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\StateBundle\Model\TransitionHandler\PaymentStateTransitionHandler');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\StateBundle\Model\TransitionHandler\ProductStateTransitionHandler');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\StateBundle\Model\TransitionHandler\ReviewStateTransitionHandler');
    }
}
