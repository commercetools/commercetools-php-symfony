<?php
/**
 */

namespace Commercetools\Symfony\CtpBundle\DependencyInjection;

use Commercetools\Symfony\CtpBundle\Model\FacetConfig;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class CommercetoolsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('commercetools.all', $config);

        $apiConfig = $config['api'];

        $keys = array_keys($apiConfig['clients']);
        $apiConfig['default_client'] = isset($apiConfig['clients'][$apiConfig['default_client']]) ? $apiConfig['default_client'] : reset($keys);

        // compatibility
        $clientConfig = $apiConfig['clients'][$apiConfig['default_client']];
        $container->setParameter('commercetools.client.config', $clientConfig);

        $clients = [];
        foreach ($apiConfig['clients'] as $name => $client) {
            $clients[$name] = [
                'service' => sprintf('commercetools.client.%s', $name),
            ];
            $this->loadClientDefinition($name, $client, $container);
        }
        $container->setParameter('commercetools.clients', $clients);
        $container->setParameter('commercetools.api.default_client', $apiConfig['default_client']);
        $container->setAlias('commercetools.client', sprintf('commercetools.client.%s', $apiConfig['default_client']));

        $container->setParameter('commercetools.fallback_languages', isset($config['fallback_languages']) ? $config['fallback_languages'] : []);

        foreach ($config['defaults'] as $key => $value) {
            $container->setParameter('commercetools.defaults.' . $key, $value);
        }

        foreach ($config['cache'] as $key => $value) {
            $container->setParameter('commercetools.cache.' . $key, $value);
        }

        $container->setParameter('commercetools.project_settings.currencies', $config['project_settings']['currencies']);
        $container->setParameter('commercetools.project_settings.countries', $config['project_settings']['countries']);
        $container->setParameter('commercetools.project_settings.languages', $config['project_settings']['languages']);

        $facetConfigs = [];
        if (isset($config['facets'])) {
            foreach ($config['facets'] as $name => $cfg) {
                $facetConfigs[$name] = $cfg;
            }
        }
        $container->setParameter('commercetools.facets', $facetConfigs);


        if (isset($config['project_settings']['name'])) {
            $container->setParameter('commercetools.project_settings.name', $config['project_settings']['name']);
        }

        if (isset($config['project_settings']['messages'])) {
            $container->setParameter('commercetools.project_settings.messages', $config['project_settings']['messages']);
        }

        if (isset($config['project_settings']['shipping_rate_input_type'])) {
            $container->setParameter('commercetools.project_settings.shipping_rate_input_type', $config['project_settings']['shipping_rate_input_type']);
        }
    }

    protected function loadClientDefinition($name, array $client, ContainerBuilder $container)
    {
        $container
            ->setDefinition(sprintf('commercetools.client.%s', $name), new ChildDefinition('commercetools.api.client'))
            ->setArguments([
                null,
                null,
                $client,
            ])
        ;
    }
}
