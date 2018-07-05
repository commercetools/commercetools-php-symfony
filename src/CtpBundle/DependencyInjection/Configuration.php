<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('commercetools');

        $rootNode
            ->children()
                ->arrayNode('api')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_client')->defaultValue('default')->end()
                        ->arrayNode('clients')
                            ->fixXmlConfig('client')
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
                        ->scalarNode('country')->defaultValue('US')->end()
                        ->arrayNode('context')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('locale')->defaultValue('en')->end()
                                ->booleanNode('graceful')->defaultValue(true)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cache')
                    ->prototype('boolean')->end()
                ->end()
                ->arrayNode('currencies')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('fallback_languages')
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
                                ->prototype('array')
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
                    ->children()
                        ->arrayNode('countries')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('languages')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('tax_categories')
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('name')->end()
                        ->booleanNode('messages')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
