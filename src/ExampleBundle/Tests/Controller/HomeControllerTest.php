<?php

namespace  Commercetools\Symfony\ExampleBundle\Tests\Controller;

use Commercetools\Symfony\ExampleBundle\Controller\HomeController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class HomeControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $request = $this->prophesize(Request::class);
        $container = $this->prophesize(ContainerInterface::class);
        $twigMock = $this->prophesize(Environment::class);

        $container->has('templating')->willReturn(false)->shouldBeCalledOnce();
        $container->has('twig')->willReturn(true)->shouldBeCalledOnce();
        $container->get('twig')->willReturn($twigMock)->shouldBeCalledOnce();

        $twigMock->render('ExampleBundle::index.html.twig', [])->shouldBeCalledOnce();

        $controller = new HomeController();
        $controller->setContainer($container->reveal());
        $controller->indexAction($request);
    }
}
