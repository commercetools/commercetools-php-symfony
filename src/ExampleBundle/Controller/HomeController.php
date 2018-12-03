<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function indexAction()
    {
        return $this->render('ExampleBundle::index.html.twig');
    }
}
