<?php
/**
 */

namespace Commercetools\Symfony\CtpBundle\Tests\DependencyInjection;

use Commercetools\Symfony\CtpBundle\DependencyInjection\CommercetoolsExtension;
use Commercetools\Symfony\CtpBundle\Tests\ContainerTrait;
use PHPUnit\Framework\TestCase;

class CommercetoolsExtensionTest extends TestCase
{
    use ContainerTrait;

    public function testLoadFirstClient()
    {
        $container = $this->getContainer();
        $extension = new CommercetoolsExtension();

        $container->registerExtension($extension);

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

        $extension->load([
            [],
            $config,
            []
        ], $container);

        $clients = $container->getParameter('commercetools.clients');

        $this->assertSame('commercetools.client.first', $clients['first']['service']);
        $this->assertSame('first', $container->getParameter('commercetools.api.default_client'));
        $this->assertSame('commercetools.client.first', (string)$container->getAlias('commercetools.client'));
    }

    public function testLoadDefaultClient()
    {
        $container = $this->getContainer();
        $extension = new CommercetoolsExtension();

        $container->registerExtension($extension);

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

        $extension->load([
            [],
            $config,
            []
        ], $container);

        $this->assertSame('default', $container->getParameter('commercetools.api.default_client'));
        $this->assertSame('commercetools.client.default', (string)$container->getAlias('commercetools.client'));
    }

    public function testLoadDefaultSecondClient()
    {
        $container = $this->getContainer();
        $extension = new CommercetoolsExtension();

        $container->registerExtension($extension);

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

        $extension->load([
            [],
            $config,
            []
        ], $container);

        $this->assertSame('second', $container->getParameter('commercetools.api.default_client'));
        $this->assertSame('commercetools.client.second', (string)$container->getAlias('commercetools.client'));
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testLoadWithWrongData()
    {
        $container = $this->getContainer();
        $extension = new CommercetoolsExtension();

        $container->registerExtension($extension);

        $extension->load([
            [],
            ['config' => []],
            []
        ], $container);

    }

    public function testLoadWithData()
    {
        $container = $this->getContainer();
        $extension = new CommercetoolsExtension();

        $container->registerExtension($extension);

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

        $extension->load([
            [],
            $config,
            []
        ], $container);

        $this->assertTrue($container->getParameter('commercetools.cache.foo'));
        $this->assertEquals(['FOO'], $container->getParameter('commercetools.currencies'));
    }
}
