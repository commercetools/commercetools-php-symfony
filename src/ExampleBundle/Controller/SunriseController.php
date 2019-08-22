<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SunriseController extends AbstractController
{
    const CSRF_TOKEN_NAME = 'csrfToken';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var CatalogManager
     */
    private $catalogManager;

    /**
     * @var CartManager
     */
    private $cartManager;

    /**
     * CartController constructor.
     * @param Client $client
     * @param CatalogManager $catalogManager
     */
    public function __construct(Client $client, CatalogManager $catalogManager, CartManager $cartManager)
    {
        $this->client = $client;
        $this->catalogManager = $catalogManager;
        $this->cartManager = $cartManager;
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

    public function getMiniCartAction(Request $request, SessionInterface $session, UserInterface $user = null)
    {
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->cartManager->getCart($request->getLocale(), $cartId, $user, $session->getId());

        if (is_null($cart)) {
            $cart = Cart::of();
        }

        return $this->render('@Example/partials/common/mini-cart-inner.html.twig', [
            'miniCart' => $cart
        ]);
    }
}
