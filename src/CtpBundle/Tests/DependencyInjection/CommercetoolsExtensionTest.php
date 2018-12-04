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
            ]
        ];

        $this->load($config, $this->getContainerExtensions());

        $clients = $this->container->getParameter('commercetools.clients');

        $this->assertSame('commercetools.client.first', $clients['first']['service']);
        $this->assertSame('first', $this->container->getParameter('commercetools.api.default_client'));
        $this->assertSame('commercetools.client.first', (string)$this->container->getAlias('commercetools.client'));
    }

    public function testLoadDefaultClient()
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
            ]
        ];

        $this->load($config, $this->getContainerExtensions());

        $this->assertSame('default', $this->container->getParameter('commercetools.api.default_client'));
        $this->assertSame('commercetools.client.default', (string)$this->container->getAlias('commercetools.client'));
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
            ]
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
            'cache' => ['foo' => true],
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

        $this->assertContainerBuilderHasParameter('commercetools.cache.foo', true);
        $this->assertContainerBuilderHasParameter('commercetools.project_settings.currencies', ['FOO']);
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
            ]
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
            ]
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
            ]
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
}
