<?php

namespace Commercetools\Symfony\CtpBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class TestKernel extends BaseKernel
{
    use MicroKernelTrait;

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    private $containerConfigurator;

    public function __construct(\Closure $containerConfigurator, $environment = 'test', $debug = false)
    {
        $this->containerConfigurator = $containerConfigurator;

        parent::__construct($environment, $debug);
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) use ($loader) {
            $container->loadFromExtension('framework', array(
                'router' => array(
                    'resource' => 'kernel::loadRoutes',
                    'type' => 'service',
                ),
            ));

            if ($this instanceof EventSubscriberInterface) {
                $container->register('kernel', static::class)
                    ->setSynthetic(true)
                    ->setPublic(true)
                    ->addTag('kernel.event_subscriber')
                ;
            }

            $this->configureContainer($container, $loader);

            $container->addObjectResource($this);
        });

        $loader->load($this->containerConfigurator);
    }

    /**
     * Override the parent method to force recompiling the container.
     * For performance reasons the container is also not dumped to disk.
     */
    protected function initializeContainer()
    {
        $this->container = $this->buildContainer();
        $this->container->compile();
        $this->container->set('kernel', $this);
    }

    public function getCacheDir()
    {
        return $this->getProjectDir().'/var/cache/'.$this->environment;
    }

    public function getLogDir()
    {
        return $this->getProjectDir().'/var/log';
    }

    public function registerBundles()
    {
        yield new \Symfony\Bundle\FrameworkBundle\FrameworkBundle();
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $container->setParameter('container.autowiring.strict_mode', true);
        $container->setParameter('container.dumper.inline_class_loader', true);
        $container->setParameter('kernel.secret', '123456');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
    }
}
