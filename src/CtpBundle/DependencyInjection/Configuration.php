<?php
/**
 */

namespace Commercetools\Symfony\CtpBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('commercetools');
        $rootNode = $this->getRootNode($treeBuilder, 'commercetools');

        $rootNode
            ->children()
                ->arrayNode('api')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('client')
                    ->children()
                        ->scalarNode('default_client')->defaultValue('default')->end()
                        ->arrayNode('clients')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('client_id')->isRequired()->end()
                                    ->scalarNode('client_secret')->isRequired()->end()
                                    ->scalarNode('project')->isRequired()->end()
                                    ->scalarNode('scope')->end()
                                    ->scalarNode('api_url')->end()
                                    ->scalarNode('oauth_url')->end()
                                    ->booleanNode('throwExceptions')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('defaults')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('country')->defaultValue('DE')->end()
                        ->arrayNode('context')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('locale')->defaultValue('en')->end()
//                                ->scalarNode('currency')->defaultValue('EUR')->end()
                                ->booleanNode('graceful')->defaultValue(true)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('setup')->defaultFalse()->end()
                        ->booleanNode('states')->defaultFalse()->end()
                        ->booleanNode('customer')->defaultFalse()->end()
                        ->booleanNode('catalog')->defaultFalse()->end()
                        ->booleanNode('cart')->defaultFalse()->end()
                        ->booleanNode('shipping_method')->defaultFalse()->end()
                        ->booleanNode('order')->defaultFalse()->end()
                        ->booleanNode('payment')->defaultFalse()->end()
                        ->booleanNode('shopping_list')->defaultFalse()->end()
                        ->booleanNode('review')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('fallback_languages')
                    ->fixXmlConfig('language')
                    ->prototype('array')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
                ->arrayNode('facets')
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('alias')->defaultValue(null)->end()
                            ->scalarNode('paramName')->defaultValue(null)->end()
                            ->scalarNode('field')->defaultValue(null)->end()
                            ->scalarNode('facetField')->defaultValue(null)->end()
                            ->scalarNode('filterField')->defaultValue(null)->end()
                            ->scalarNode('multiSelect')->defaultValue(true)->end()
                            ->scalarNode('hierarchical')->defaultValue(false)->end()
                            ->scalarNode('display')->defaultValue('2column')->end()
                            ->scalarNode('type')->defaultValue('enum')->end()
                            ->arrayNode('ranges')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('from')->end()
                                        ->scalarNode('to')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('project_settings')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('country', 'countries')
                    ->fixXmlConfig('currency', 'currencies')
                    ->fixXmlConfig('language')
                    ->children()
                        ->arrayNode('countries')
                            ->requiresAtLeastOneElement()
                            ->defaultValue(['DE'])
                            ->prototype('scalar')
                                ->beforeNormalization()
                                    ->always()
                                    ->then(function ($v) {
                                        return strtoupper($v);
                                    })
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('currencies')
                            ->requiresAtLeastOneElement()
                            ->defaultValue(['EUR'])
                            ->prototype('scalar')
                                ->beforeNormalization()
                                    ->always()
                                    ->then(function ($v) {
                                        return strtoupper($v);
                                    })
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('languages')
                            ->requiresAtLeastOneElement()
                            ->defaultValue(['en'])
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('name')->end()
                        ->arrayNode('messages')
                            ->beforeNormalization()
                                ->ifTrue(function ($v) {
                                    return !is_array($v);
                                })
                                ->then(function ($v) {
                                    return ['enabled' => $v == true ?? false];
                                })
                            ->end()
                            ->children()
                                ->booleanNode('enabled')->end()
                            ->end()
                        ->end()
                        ->arrayNode('shipping_rate_input_type')
                            ->children()
                                ->scalarNode('type')->isRequired()->end()
                                ->arrayNode('values')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('key')->end()
                                            ->arrayNode('label')
                                                ->prototype('scalar')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->variableNode('custom_types')->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function getRootNode(TreeBuilder $treeBuilder, $name)
    {
        // BC layer for symfony/config 4.1 and older
        if (! \method_exists($treeBuilder, 'getRootNode')) {
            return $treeBuilder->root($name);
        }
        return $treeBuilder->getRootNode();
    }
}
