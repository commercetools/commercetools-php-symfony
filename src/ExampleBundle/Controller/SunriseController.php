<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Symfony\CartBundle\Manager\MeCartManager;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToCartType;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\ContactUsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class SunriseController extends AbstractController
{
    /**
     * @var CatalogManager
     */
    private $catalogManager;

    /**
     * @var MeCartManager
     */
    private $cartManager;

    /**
     * CartController constructor.
     * @param CatalogManager $catalogManager
     * @param MeCartManager $cartManager
     */
    public function __construct(CatalogManager $catalogManager, MeCartManager $cartManager)
    {
        $this->catalogManager = $catalogManager;
        $this->cartManager = $cartManager;
    }

    public function homeAction()
    {
        return $this->render('@Example/home.html.twig');
    }

    public function faqAction()
    {
        return $this->render('@Example/faq.html.twig');
    }

    public function helpAction()
    {
        return $this->render('@Example/home.html.twig');
    }

    public function locateStoreAction()
    {
        return $this->render('@Example/store-finder.html.twig');
    }


    public function contactAction()
    {
        $addToCartForm = $this->createForm(ContactUsType::class);

        return $this->render('@Example/contact-form.html.twig', [
            'form' => $addToCartForm->createView()
        ]);
    }

    public function getNavMenuAction(Request $request, $sort = 'id asc')
    {
        $params = QueryParams::of()->add('sort', $sort);

        $categories = $this->catalogManager->getCategories($request->getLocale(), $params);

        return $this->render('@Example/partials/common/nav-menu.html.twig', [
            'navMenu' => [
                'new' => true,
                'categories' => $categories
            ]
        ]);
    }

    public function getMiniCartAction(Request $request)
    {
        $cart = $this->cartManager->getCart($request->getLocale()) ?? Cart::of();

        return $this->render('@Example/partials/common/mini-cart-inner.html.twig', [
            'miniCart' => $cart
        ]);
    }
}
