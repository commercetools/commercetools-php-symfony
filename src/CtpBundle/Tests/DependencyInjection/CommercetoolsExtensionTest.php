<?php
/**
 */

namespace Commercetools\Symfony\CtpBundle\Tests\DependencyInjection;

use Commercetools\Symfony\CtpBundle\DependencyInjection\CommercetoolsExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class CommercetoolsExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new CommercetoolsExtension()
        ];
    }

    public function testLoadFirstClient()
    {
        $config = [
            'api' => [
                'clients' => [
                    'first' => [
                        'client_id' => '123',
                        'client_secret' => '456',
                        'project' => 'foo'
                    ],
                    'second' => [
                        'client_id' => '789',
                        'client_secret' => '101',
                        'project' => 'bar'
                    ]
                ]
            ],
            'project_settings' => [
                'currencies' => ['EUR']
            ],
            'cache' => ['cart' => true]
        ];

        $this->load($config, $this->getContainerExtensions());

        $clients = $this->container->getParameter('commercetools.clients');

        $this->assertSame('commercetools.client.first', $clients['first']['service']);
        $this->assertSame('first', $this->container->getParameter('commercetools.api.default_client'));
        $this->assertSame(true, $this->container->getParameter('commercetools.cache.cart'));
        $this->assertSame(false, $this->container->getParameter('commercetools.cache.catalog'));
        $this->assertSame('commercetools.client.first', (string)$this->container->getAlias('commercetools.client'));
    }

    public function testLoadDefaultClientAndDefaultCacheData()
    {
        $config = [
            'api' => [
                'clients' => [
                    'first' => [
                        'client_id' => '123',
                        'client_secret' => '456',
                        'project' => 'foo'
                    ],
                    'default' => [
                        'client_id' => '789',
                        'client_secret' => '101',
                        'project' => 'bar'
                    ]
                ]
            ],
            'project_settings' => [
                'currencies' => ['eur']
            ],
            'cache' => []
        ];

        $this->load($config, $this->getContainerExtensions());

        $this->assertSame('default', $this->container->getParameter('commercetools.api.default_client'));
        $this->assertSame('commercetools.client.default', (string)$this->container->getAlias('commercetools.client'));

        $this->assertContainerBuilderHasParameter('commercetools.cache.cart', false);
        $this->assertContainerBuilderHasParameter('commercetools.cache.shipping_method', false);
        $this->assertContainerBuilderHasParameter('commercetools.cache.order', false);
        $this->assertContainerBuilderHasParameter('commercetools.cache.payment', false);
        $this->assertContainerBuilderHasParameter('commercetools.cache.catalog', false);
        $this->assertContainerBuilderHasParameter('commercetools.cache.customer', false);
        $this->assertContainerBuilderHasParameter('commercetools.cache.review', false);
        $this->assertContainerBuilderHasParameter('commercetools.cache.setup', false);
        $this->assertContainerBuilderHasParameter('commercetools.cache.shopping_list', false);
        $this->assertContainerBuilderHasParameter('commercetools.cache.states', false);
    }

    public function testLoadDefaultSecondClient()
    {
        $config = [
            'api' => [
                'default_client' => 'second',
                'clients' => [
                    'first' => [
                        'client_id' => '123',
                        'client_secret' => '456',
                        'project' => 'foo'
                    ],
                    'second' => [
                        'client_id' => '789',
                        'client_secret' => '101',
                        'project' => 'bar'
                    ]
                ]
            ],
            'project_settings' => [
                'currencies' => ['usd']
            ],
            'cache' => []
        ];

        $this->load($config, $this->getContainerExtensions());

        $this->assertSame('second', $this->container->getParameter('commercetools.api.default_client'));
        $this->assertSame('commercetools.client.second', (string)$this->container->getAlias('commercetools.client'));
        $this->assertContainerBuilderHasParameter('commercetools.project_settings.currencies', ['USD']);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testLoadWithWrongData()
    {
        $this->load(['config' => []], $this->getContainerExtensions());
    }

    public function testLoadWithData()
    {
        $config = [
            'cache' => ['catalog' => true],
            'api' => [ 'clients' => [
                'first' => [
                    'client_id' => 'foo',
                    'client_secret' => 'bar',
                    'project' => 'other'
                ]
            ] ],
            'project_settings' => [
                'currencies' => ['foo']
            ]
        ];

        $this->load($config, $this->getContainerExtensions());

        $this->assertContainerBuilderHasParameter('commercetools.cache.catalog', true);
        $this->assertContainerBuilderHasParameter('commercetools.project_settings.currencies', ['FOO']);
        $this->assertSame(true, $this->container->getParameter('commercetools.cache.catalog'));
        $this->assertSame(false, $this->container->getParameter('commercetools.cache.cart'));
    }

    public function testLoadWithProjectSettings()
    {
        $config = [
            'api' => [ 'clients' => [
                'first' => [
                    'client_id' => 'foo',
                    'client_secret' => 'bar',
                    'project' => 'other'
                ]
            ] ],
            'project_settings' => [
                'currencies' => ['foo'],
                'countries' => ['DE'],
                'languages' => ['en'],
                'name' => 'project',
                'messages' => true,
                'shipping_rate_input_type' => ['type' => 'CartValue']
            ],
            'cache' => []
        ];

        $this->load($config, $this->getContainerExtensions());

        $this->assertContainerBuilderHasParameter('commercetools.project_settings.currencies', ['FOO']);
        $this->assertContainerBuilderHasParameter('commercetools.project_settings.countries', ['DE']);
        $this->assertContainerBuilderHasParameter('commercetools.project_settings.languages', ['en']);
        $this->assertContainerBuilderHasParameter('commercetools.project_settings.name', 'project');
        $this->assertContainerBuilderHasParameter('commercetools.project_settings.messages', ['enabled' => true]);

        $expectedShippingRateInputType = [
            'type' => 'CartValue',
            'values' => []
        ];
        $this->assertContainerBuilderHasParameter('commercetools.project_settings.shipping_rate_input_type', $expectedShippingRateInputType);
    }

    public function testLoadServices()
    {
        $config = [
            'api' => [ 'clients' => [
                'first' => [
                    'client_id' => 'foo',
                    'client_secret' => 'bar',
                    'project' => 'other'
                ]
            ] ],
            'project_settings' => [
                'currencies' => ['foo']
            ],
            'cache' => []
        ];

        $this->load($config, $this->getContainerExtensions());

        $this->assertContainerBuilderHasSyntheticService('commercetools');
        $this->assertContainerBuilderHasService('Commercetools\Core\Client');
        $this->assertContainerBuilderHasService('commercetools.api.client', 'Commercetools\Core\Client');
        $this->assertContainerBuilderHasService('commercetools.client.config', 'Commercetools\Core\Config');
        $this->assertContainerBuilderHasService('commercetools.client.factory', 'Commercetools\Symfony\CtpBundle\Service\ClientFactory');
    }

    public function testLoadWithFacets()
    {
        $config = [
            'api' => [ 'clients' => [
                'first' => [
                    'client_id' => 'foo',
                    'client_secret' => 'bar',
                    'project' => 'other'
                ]
            ] ],
            'project_settings' => [],
            'facets' => [
                'foobar' => [
                    'alias' => 'bar',

                ]
            ],
            'cache' => []
        ];

        $expected = [
            'foobar' => [
                'alias' => 'bar',
                'paramName' => null,
                'field' => null,
                'facetField' => null,
                'filterField' => null,
                'multiSelect' => true,
                'hierarchical' => false,
                'display' => '2column',
                'type' => 'enum',
                'ranges' => []
            ]
        ];

        $this->load($config, $this->getContainerExtensions());
        $this->assertContainerBuilderHasParameter('commercetools.facets', $expected);
    }

    public function testGetNamespace()
    {
        $extension = new CommercetoolsExtension();

        $this->assertSame('http://commercetools.com/schema/dic/ctp', $extension->getNamespace());
    }

    public function testGetXsdValidationPath()
    {
        $extension = new CommercetoolsExtension();

        $this->assertContains('/../Resources/config/schema', $extension->getXsdValidationBasePath());
    }
}
