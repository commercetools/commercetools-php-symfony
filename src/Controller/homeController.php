<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class homeController extends Controller
{
    public function indexAction()
    {
        return $this->render('CtpBundle:index.html.twig');
    }
}