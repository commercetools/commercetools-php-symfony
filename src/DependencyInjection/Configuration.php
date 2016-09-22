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
                ->arrayNode('client_config')
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
                ->arrayNode('currency')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('fallback_languages')
                    ->prototype('array')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
