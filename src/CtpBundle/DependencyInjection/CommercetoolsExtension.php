<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
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

        $container->getParameter('kernel.root_dir');
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

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

        $container->setParameter('commercetools.fallback_languages', isset($config['fallback_languages']) ? $config['fallback_languages']: []);

        foreach ($config['defaults'] as $key => $value) {
            $container->setParameter('commercetools.defaults.' . $key, $value);
        }

        foreach ($config['cache'] as $key => $value) {
            if (is_string($value)) {
                $value = ($value == "true");
            }
            $container->setParameter('commercetools.cache.' . $key, $value);
        }
        foreach ($config['currency'] as $key => $value) {
            $container->setParameter('commercetools.currency.' . strtolower($key), $value);
        }

        $facetConfigs = [];
        if (isset($config['facets'])) {
            foreach ($config['facets'] as $name => $cfg) {
                $facetConfigs[$name] = $cfg;
            }
        }
        $container->setParameter('commercetools.facets', $facetConfigs);

        if (isset($config['project_settings']['countries'])) {
            $container->setParameter('commercetools.project_settings.countries', $config['project_settings']['countries']);
        }

        if (isset($config['project_settings']['languages'])) {
            $container->setParameter('commercetools.project_settings.languages', $config['project_settings']['languages']);
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
