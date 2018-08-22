<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\DependencyInjection\Compiler;


use Commercetools\Symfony\StateBundle\Model\CtpMarkingStore;
use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CtpStateMachinePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('workflow.definition') as $id => $tags) {
            $workflowDefinition = $container->getDefinition($id);
            $workflowServiceDefinition = $container->getDefinition(str_replace('.definition', '', $id));

//            dump($workflowServiceDefinition);
            $stateMachineName = $workflowServiceDefinition->getArgument(3);
            $initialState = $workflowDefinition->getArgument(2);

            $newMarkingStore = new Definition(CtpMarkingStore::class);
            $newMarkingStore->setArgument('$stateRepository', new Reference(StateRepository::class));
            $newMarkingStore->setArgument('$cache', new Reference('cache.app'));
            $newMarkingStore->setArgument('$initialState', $initialState);

            $stateMachineName = 'ctp.marking_store.' . $stateMachineName;

            $container->setDefinition($stateMachineName, $newMarkingStore);

            $workflowServiceDefinition->replaceArgument('$markingStore', new Reference($stateMachineName));
//            dump($workflowServiceDefinition);
        }
        $container->removeDefinition('ctp.marking_store');
    }
}
