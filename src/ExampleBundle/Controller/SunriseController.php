<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Category\Category;
use Commercetools\Core\Model\Category\CategoryCollection;
use Commercetools\Symfony\CartBundle\Manager\MeCartManager;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\ContactUsType;
use Commercetools\Symfony\ExampleBundle\Model\View\NavMenu;
use Commercetools\Symfony\ExampleBundle\Model\View\Tree;
use Commercetools\Symfony\ExampleBundle\Model\View\Url;
use Commercetools\Symfony\ExampleBundle\Model\ViewDataCollection;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
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

    /** @var CacheItemPoolInterface */
    private $cache;

    /**
     * CartController constructor.
     * @param CacheItemPoolInterface $cache
     * @param CatalogManager $catalogManager
     * @param MeCartManager $cartManager
     */
    public function __construct(CacheItemPoolInterface $cache, CatalogManager $catalogManager, MeCartManager $cartManager)
    {
        $this->cache = $cache;
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
        return $this->render('@Example/faq.html.twig');
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
        $params->add('limit', 500);

        $categories = $this->catalogManager->getCategories($request->getLocale(), $params);
        $catMenu = $this->getNavMenu($request->getLocale(), $categories);

        return $this->render('@Example/partials/common/nav-menu.html.twig', [
            'navMenu' => $catMenu
        ]);
    }

    public function getMiniCartAction(Request $request)
    {
        $cart = $this->cartManager->getCart($request->getLocale()) ?? Cart::of();

        return $this->render('@Example/partials/common/mini-cart-inner.html.twig', [
            'miniCart' => $cart
        ]);
    }

    protected function getNavMenu($locale, CategoryCollection $categoriesCollection)
    {
        $navMenu = new NavMenu();

        $cacheKey = 'category-menu-' . $locale;
        if ($this->cache->hasItem($cacheKey)) {
            /**
             * @var CacheItemInterface $item
             */
            $item = $this->cache->getItem($cacheKey);
            $categoryMenu = $item->get();
        } else {
            $categoryMenu = new ViewDataCollection();
            $roots = $this->sortCategoriesByOrderHint($categoriesCollection->getRoots());

            foreach ($roots as $root) {
                /**
                 * @var Category $root
                 */
                $menuEntry = new Tree(
                    (string)$root->getName(),
                    $this->generateUrl('_ctp_example_products_of_category_with_slug', ['categorySlug' => $root->getSlug()])
                );
//                if ($root->getSlug() == $this->config['sunrise.sale.slug']) {
                if ($root->getSlug() == '/sale') {
                    $menuEntry->sale = true;
                }

                $subCategories = $this->sortCategoriesByOrderHint($categoriesCollection->getByParent($root->getId()));
                foreach ($subCategories as $children) {
                    /**
                     * @var Category $children
                     */
                    $childrenEntry = new Tree(
                        (string)$children->getName(),
                        $this->generateUrl('_ctp_example_products_of_category_with_slug', ['categorySlug' => $children->getSlug()])
                    );

                    $subChildCategories = $this->sortCategoriesByOrderHint($categoriesCollection->getByParent($children->getId()));
                    foreach ($subChildCategories as $subChild) {
                        /**
                         * @var Category $subChild
                         */
                        $childrenSubEntry = new Url(
                            (string)$subChild->getName(),
                            $this->generateUrl('_ctp_example_products_of_category_with_slug', ['categorySlug' => $subChild->getSlug()])
                        );
                        $childrenEntry->addNode($childrenSubEntry);
                    }
                    $menuEntry->addNode($childrenEntry);
                }
                $categoryMenu->add($menuEntry);
            }
            $categoryMenu = $categoryMenu->toArray();
            $item = $this->cache->getItem($cacheKey)->set($categoryMenu)->expiresAfter(Repository::CACHE_TTL);
            $this->cache->save($item);
        }
        $navMenu->categories = $categoryMenu;

        return $navMenu;
    }

    protected function sortCategoriesByOrderHint($categories)
    {
        usort($categories, function (Category $a, Category $b) {
            return $a->getOrderHint() > $b->getOrderHint();
        });

        return $categories;
    }
}
