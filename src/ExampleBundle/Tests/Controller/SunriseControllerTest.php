<?php

namespace  Commercetools\Symfony\ExampleBundle\Tests\Controller;

use Commercetools\Symfony\CartBundle\Manager\MeCartManager;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Commercetools\Symfony\ExampleBundle\Controller\SunriseController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class SunriseControllerTest extends WebTestCase
{
    /** @var CatalogManager */
    private $catalogManager;
    /** @var MeCartManager */
    private $meCartManager;

    public function setUp(): void
    {
        $this->catalogManager = $this->prophesize(CatalogManager::class);
        $this->meCartManager = $this->prophesize(MeCartManager::class);
    }

    public function testHome()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $twigMock = $this->prophesize(Environment::class);

        $container->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $container->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $container->get('twig')->willReturn($twigMock)->shouldBeCalledOnce();

        $twigMock->render('@Example/home.html.twig', [])->shouldBeCalledOnce();

        $controller = new SunriseController($this->catalogManager->reveal(), $this->meCartManager->reveal());
        $controller->setContainer($container->reveal());
        $controller->homeAction();
    }
}
