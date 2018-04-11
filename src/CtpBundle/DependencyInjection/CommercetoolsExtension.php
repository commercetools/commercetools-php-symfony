<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\DependencyInjection;

use Commercetools\Symfony\CtpBundle\Model\FacetConfig;
use Symfony\Component\Config\FileLocator;
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

        $container->setParameter('commercetools.fallback_languages', isset($config['fallback_languages']) ? $config['fallback_languages']: []);

//        if (!isset($config['clients']['default_client'])) {
//            $keys = array_keys($config['clients']);
//            $config['clients']['default_client'] = reset($keys);
//        }

        $container->setParameter('commercetools.clients', $config['clients']);

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
            foreach ($config['facets'] as $name => $config) {
                $facetConfigs[$name] = $config;
            }
        }
        $container->setParameter('commercetools.facets', $facetConfigs);
    }
}
