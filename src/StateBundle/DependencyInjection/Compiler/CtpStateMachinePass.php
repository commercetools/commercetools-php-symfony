<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\DependencyInjection\Compiler;

use Commercetools\Symfony\StateBundle\Cache\StateKeyResolver;
use Commercetools\Symfony\StateBundle\EventListener\TransitionSubscriber;
use Commercetools\Symfony\StateBundle\Model\CtpMarkingStore\CtpMarkingStore;
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
            $initialState = $workflowDefinition->getArgument(2);

            $workflowServiceDefinition = $container->getDefinition(str_replace('.definition', '', $id));
            $stateMachineName = $workflowServiceDefinition->getArgument(3);

            $newMarkingStore = new Definition(CtpMarkingStore::class . $stateMachineName);
            $newMarkingStore->setArgument('$stateKeyResolver', new Reference(StateKeyResolver::class));
            $newMarkingStore->setArgument('$initialState', $initialState);

            $fullClassName = CtpMarkingStore::class . $stateMachineName;
            $container->setDefinition($fullClassName, $newMarkingStore);

            $container->register('transition_listener.' . $stateMachineName, TransitionSubscriber::class)
                ->addArgument(new Reference('Commercetools\Symfony\StateBundle\Model\TransitionHandler\\' . $stateMachineName . 'TransitionHandler'))
                ->addTag('kernel.event_listener', [
                    'event' => 'workflow.' . $stateMachineName . '.transition',
                    'method' => 'transitionSubject'
                ]);
        }
    }
}
