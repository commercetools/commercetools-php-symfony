<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\DependencyInjection\Compiler;


use Commercetools\Symfony\StateBundle\Model\CtpMarkingStore;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CtpStateMachinePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('workflow.definition') as $id => $tags) {
            $workflowDefinition = $container->getDefinition($id);
            $initialState = $workflowDefinition->getArgument(2);

            $workflowServiceDefinition = $container->getDefinition(str_replace('.definition', '', $id));
            $stateMachineName = $workflowServiceDefinition->getArgument(3);

            $basePath = CtpMarkingStore::class;
            $service = $container->getDefinition($basePath . $stateMachineName);
            $service->setArgument('$initialState', $initialState);

        }
    }
}
