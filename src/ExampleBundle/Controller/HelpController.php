<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HelpController extends AbstractController
{
    public function indexAction()
    {
        return $this->render('@Example/home.html.twig');
    }
}
