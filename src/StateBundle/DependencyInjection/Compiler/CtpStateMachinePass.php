<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\DependencyInjection\Compiler;


use Commercetools\Core\Model\Order\ItemState;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\StateBundle\Model\CtpMarkingStore;
use Commercetools\Symfony\StateBundle\Model\CtpMarkingStoreOrderState;
use Commercetools\Symfony\StateBundle\Model\CtpMarkingStoreLineItemState;
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
            $initialState = $workflowDefinition->getArgument(2);

            $workflowServiceDefinition = $container->getDefinition(str_replace('.definition', '', $id));
            $stateMachineName = $workflowServiceDefinition->getArgument(3);

            $basePath = CtpMarkingStore::class;
            $newMarkingStore = new Definition($basePath . $stateMachineName);
            $newMarkingStore->setArgument('$stateRepository', new Reference(StateRepository::class));
            $newMarkingStore->setArgument('$cache', new Reference('cache.app'));
            $newMarkingStore->setArgument('$initialState', $initialState);

            // TODO
            $newMarkingStore->setArgument('$manager', new Reference(OrderManager::class));

            $stateMachineName = 'ctp.marking_store.' . $stateMachineName;

            $container->setDefinition($stateMachineName, $newMarkingStore);

            $workflowServiceDefinition->replaceArgument('$markingStore', new Reference($stateMachineName));
        }

        $container->removeDefinition('ctp.marking_store');
    }
}
