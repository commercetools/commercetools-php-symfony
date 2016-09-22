<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\DependencyInjection;

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
        if (isset($config['client_config'])) {
            $clientConfig = $config['client_config'];
            $container->setParameter('commercetools.client.config', $clientConfig);
        }

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
    }
}
