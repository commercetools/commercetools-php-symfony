<?php

namespace  Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\Product\Search\Filter;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\ExampleBundle\Entity\ProductEntity;
use Commercetools\Symfony\ExampleBundle\Entity\ProductToShoppingList;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToCartType;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToShoppingListType;
use Commercetools\Symfony\ExampleBundle\Model\View\ProductModel;
use Commercetools\Symfony\ExampleBundle\Model\ViewData;
use Commercetools\Symfony\ExampleBundle\Model\ViewDataCollection;
use GuzzleHttp\Psr7\Uri;
use Commercetools\Symfony\ExampleBundle\Model\View\Url;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\ShoppingList\ShoppingList;
use function GuzzleHttp\Psr7\parse_query;

class CatalogController extends AbstractController
{
    const PAGE_SELECTOR_RANGE = 2;
    const FIRST_PAGE = 1;
    const ITEMS_PER_PAGE = 12;

    private $catalogManager;
    private $shoppingListManager;

    /**
     * CatalogController constructor.
     * @param CatalogManager|null $catalogManager
     * @param ShoppingListManager|null $shoppingListManager
     */
    public function __construct(CatalogManager $catalogManager = null, ShoppingListManager $shoppingListManager = null)
    {
        $this->catalogManager = $catalogManager;
        $this->shoppingListManager = $shoppingListManager;
    }

    public function indexAction(Request $request, $categoryId = null, $productTypeId = null, $categorySlug = null)
    {
        $form = $this->createFormBuilder()
            ->add(
                'search',
                TextType::class,
                [
                    'attr' => [
                        'placeholder' => 'Search...',
                    ],
                    'label' => false,
                    'required' => false,
                ]
            )
            ->add('save', SubmitType::class, ['label' => 'Search'])
            ->getForm();
        $form->handleRequest($request);

        $search = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $search = $form->get('search')->getData();
        }

        $uri = new Uri($request->getRequestUri());
        $queryVars = parse_query($uri);

        $page = $queryVars['page'] ?? 1;
        $offset = min(self::ITEMS_PER_PAGE * ($page - 1), 100000);

        $category = null;
        $filter = null;

        if (!is_null($categoryId)) {
            $categories = $this->catalogManager->getCategories($request->getLocale());
            $category = $categories->getById($categoryId);

            $filter['filter.query'][] = Filter::ofName('categories.id')->setValue($categoryId);
        }

        if (!is_null($productTypeId)) {
            $filter['filter.query'][] = Filter::ofName('productType.id')->setValue($productTypeId);
        }

        if (!is_null($categorySlug)) {
            $categories = $this->catalogManager->getCategories($request->getLocale());
            $category = $categories->getBySlug($categorySlug, $request->getLocale());

            $filter['filter.query'][] = Filter::ofName('categories.id')->setValue($category->getId());
        }

        list($products, $facets, $offset) = $this->catalogManager->searchProducts(
            $request->getLocale(),
            self::ITEMS_PER_PAGE,
            $offset,
            'id asc',
            $this->getCurrencyFromConfig(),
            $this->getCountryFromConfig(),
            new Uri($request->getRequestUri()),
            $search,
            $filter
        );

        return $this->render('@Example/pop.html.twig', [
            'products' => $products,
            'facets' => $facets,
            'offset' => $offset,
            'category' => $category,
            'form' => $form->createView()
        ]);
    }

    public function detailBySlugAction(Request $request, $slug, SessionInterface $session, UserInterface $user = null, CacheItemPoolInterface $cache = null)
    {
        $country = $this->getCountryFromConfig();
        $currency = $this->getCurrencyFromConfig();

        try {
            $product = $this->catalogManager->getProductBySlug($request->getLocale(), $slug, $currency, $country);
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error', sprintf('Cannot find product: %s', $slug));
            return $this->render('@Example/no-search-result.html.twig');
        }

        return $this->productDetails($request, $product, $session, $user, $cache);
    }

    public function detailByIdAction(Request $request, $id, SessionInterface $session, UserInterface $user = null)
    {
        $product = $this->catalogManager->getProductById($request->getLocale(), $id);

        return $this->productDetails($request, $product, $session, $user);
    }

    private function productDetails(Request $request, ProductProjection $product, SessionInterface $session, UserInterface $user = null, CacheItemPoolInterface $cache = null)
    {
        // o-s
        $variantIds = [];
        foreach ($product->getAllVariants() as $variant) {
            $variantIds[$variant->getSku()] = $variant->getId();
        }

//        dump($variantIds);

        $shoppingListsIds = [];
        if (is_null($user)) {
            $shoppingLists = $this->shoppingListManager->getAllOfAnonymous($request->getLocale(), $session->getId());
        } else {
            $shoppingLists = $this->shoppingListManager->getAllOfCustomer($request->getLocale(), CustomerReference::ofId($user->getId()));
        }

        foreach ($shoppingLists as $shoppingList) {
            /** @var ShoppingList $shoppingList */
            $shoppingListsIds[(string)$shoppingList->getName()] = $shoppingList->getId();
        }

        $productEntity = new ProductEntity();
        $productEntity->setProductId($product->getId())
            ->setSlug((string)$product->getSlug())
            ->setAllVariants($variantIds);

        $addToCartForm = $this->createForm(AddToCartType::class, $productEntity, ['action' => $this->generateUrl('_ctp_example_add_lineItem')]);
        $addToCartForm->handleRequest($request);

        $productToShoppingList = new ProductToShoppingList();
        $productToShoppingList->setProductId($product->getId())
            ->setSlug((string)$product->getSlug())
            ->setAllVariants($variantIds)
            ->setAvailableShoppingLists($shoppingListsIds);

        $addToShoppingListForm = $this->createForm(AddToShoppingListType::class, $productToShoppingList, ['action' => $this->generateUrl('_ctp_example_shoppingList_add_lineItem')]);
        $addToShoppingListForm->handleRequest($request);

        // o-e

        // n-s

        $locale = $request->getLocale();
        $country = $this->getCountryFromConfig();
        $currency = $this->getCurrencyFromConfig();

        $slug = $request->get('slug');
        $sku = $request->get('sku');

//        $viewData = new ViewData();
//
//        $product = $this->catalogManager->getProductBySlug($locale, $slug, $currency, $country);
//        $productData = $this->getProductModel($cache)->getProductDetailData($product, $sku, $locale);
//        $viewData->content = new ViewData();
//        $viewData->content->product = $productData;

//        dump($viewData);

        // n-e

        return $this->render('@Example/pdp.html.twig', [
            'product' =>  $product,
            'addToCartForm' => $addToCartForm->createView(),
            'addToShoppingListForm' => $addToShoppingListForm->createView()
        ]);
    }

    public function suggestAction(Request $request, $searchTerm)
    {
        $country = $this->getCountryFromConfig();
        $currency = $this->getCurrencyFromConfig();

        $products = $this->catalogManager->suggestProducts($request->getLocale(), $searchTerm, 5, $currency, $country);

        $items = [];

        /**
         * @var ProductProjection $product
         */
        foreach ($products as $product) {
            $items[$product->getId()] = [];
            $items[$product->getId()]['link'] = (string)$product->getSlug();
            $items[$product->getId()]['name'] = (string)$product->getName();
            $items[$product->getId()]['image'] = (string)$product->getMasterVariant()->getImages()->current()->getUrl();
            $items[$product->getId()]['desc'] = (string)$product->getDescription();
            $items[$product->getId()]['price'] = (string)$product->getMasterVariant()->getPrice()->getCurrentValue();
        }

        $res = new JsonResponse();
        $res->setData($items);

        return $res;
    }

    public function getCategoriesAction(Request $request, $sort = 'id asc')
    {
        $params = QueryParams::of()->add('sort', $sort);

        $categories = $this->catalogManager->getCategories($request->getLocale(), $params);

        return $this->render('@Example/catalog/categoriesList.html.twig', [
            'categories' => $categories
        ]);
    }

    public function getProductTypesAction(Request $request, $sort = 'id asc')
    {
        $params = QueryParams::of()->add('sort', $sort);

        $productTypes = $this->catalogManager->getProductTypes($request->getLocale(), $params);

        return $this->render('@Example/catalog/productTypesList.html.twig', [
            'productTypes' => $productTypes
        ]);
    }

    protected function getProductModel($cache)
    {
        $model = new ProductModel(
            $cache,
            $this->catalogManager,
            $this->getCountryFromConfig(),
            $this->getCurrencyFromConfig()
        );

        return $model;
    }

    protected function applyPagination(UriInterface $uri, $offset, $total, $itemsPerPage)
    {
        $firstPage = static::FIRST_PAGE;
        $pageRange = static::PAGE_SELECTOR_RANGE;
        $currentPage = floor($offset / max(1, $itemsPerPage)) + 1;
        $totalPages = ceil($total / max(1, $itemsPerPage));

        $displayedPages = $pageRange * 2 + 3;
        $pageThresholdLeft = $displayedPages - $pageRange;
        $thresholdPageLeft = $displayedPages - 1;
        $pageThresholdRight = $totalPages - $pageRange - 2;
        $thresholdPageRight = $totalPages - $displayedPages + 2;
        $pagination = new ViewData();

        if ($totalPages <= $displayedPages) {
            $pagination->pages = $this->getPages($uri, $firstPage, $totalPages, $currentPage);
        } elseif ($currentPage < $pageThresholdLeft) {
            $pagination->pages = $this->getPages($uri, $firstPage, $thresholdPageLeft, $currentPage);
            $pagination->lastPage = $this->getPageUrl($uri, $totalPages);
        } elseif ($currentPage > $pageThresholdRight) {
            $pagination->pages = $this->getPages($uri, $thresholdPageRight, $totalPages, $currentPage);
            $pagination->firstPage = $this->getPageUrl($uri, $firstPage);
        } else {
            $pagination->pages = $this->getPages(
                $uri,
                $currentPage - $pageRange,
                $currentPage + $pageRange,
                $currentPage
            );
            $pagination->firstPage = $this->getPageUrl($uri, $firstPage);
            $pagination->lastPage = $this->getPageUrl($uri, $totalPages);
        }

        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            $pagination->previousUrl = $this->getPageUrl($uri, $prevPage)->url;
        }
        if ($currentPage < $totalPages) {
            $nextPage = $currentPage + 1;
            $pagination->nextUrl = $this->getPageUrl($uri, $nextPage)->url;
        }

        $this->pagination = $pagination;
    }

    protected function getPages(UriInterface $uri, $start, $stop, $currentPage)
    {
        $pages = new ViewDataCollection();
        for ($i = $start; $i <= $stop; $i++) {
            $url = $this->getPageUrl($uri, $i);
            if ($currentPage == $i) {
                $url->selected = true;
            }
            $pages->add($url);
        }
        return $pages;
    }

    protected function getPageUrl(UriInterface $uri, $number, $query = 'page')
    {
        $url = new Url($number, Uri::withQueryValue($uri, $query, $number));
        return $url;
    }

    // TODO duplicate code / move these to better place
    private function getCountryFromConfig()
    {
        $countries = $this->getParameter('commercetools.project_settings.countries');
        return current($countries);
    }

    private function getCurrencyFromConfig()
    {
        $currencies = $this->getParameter('commercetools.project_settings.currencies');
        return current($currencies);
    }
}
