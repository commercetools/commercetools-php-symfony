<?php

namespace  Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\Product\Search\Filter;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToCartType;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToShoppingListType;
use GuzzleHttp\Psr7\Uri;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\Security\Core\User\UserInterface;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\ShoppingList\ShoppingList;

class CatalogController extends Controller
{
    private $client;
    private $catalogManager;
    private $shoppingListManager;

    /**
     * CatalogController constructor.
     */
    public function __construct(Client $client, CatalogManager $catalogManager = null, ShoppingListManager $shoppingListManager = null)
    {
        $this->client = $client;
        $this->catalogManager = $catalogManager;
        $this->shoppingListManager = $shoppingListManager;
    }
    public function indexAction(Request $request, $categoryId = null, $productTypeId = null)
    {
        $form = $this->createFormBuilder()
            ->add('search', TextType::class,
                [
                    'attr' => [
                        'placeholder' => 'Search...',
                    ],
                    'label' => false,
                    'required' => false,
                ])
            ->add('save', SubmitType::class, ['label' => 'Search'])
            ->getForm();
        $form->handleRequest($request);

        $search = null;
        if ($form->isSubmitted() && $form->isValid()){
            $search = $form->get('search')->getData();
        }

        $uri = new Uri($request->getRequestUri());

        $filter = null;
        if(!is_null($categoryId)){
            $filter['filter'][] = Filter::ofName('categories.id')->setValue($categoryId);
        }

        if(!is_null($productTypeId)){
            $filter['filter'][] = Filter::ofName('productType.id')->setValue($productTypeId);
        }

        list($products, $offset) = $this->catalogManager->getProducts(
            $request->getLocale(), 12, 1, 'price asc', 'EUR', 'DE', $uri, $search, $filter
        );

        $categories = $this->catalogManager->getCategories($request->getLocale(),'id asc');
        $productTypes = $this->catalogManager->getProductTypes($request->getLocale(),'id asc');

        return $this->render('ExampleBundle:catalog:index.html.twig', [
                'products' => $products,
                'offset' => $offset,
                'categories' => $categories,
                'productTypes' => $productTypes,
                'form' => $form->createView(),
        ]);

    }

    public function detailBySlugAction(Request $request, $slug, UserInterface $user = null)
    {
        $product = $this->catalogManager->getProductBySlug($slug, $request->getLocale(), 'EUR', 'DE');

        return $this->productDetails($request, $product, $user);
    }

    public function detailByIdAction(Request $request, $id, UserInterface $user = null)
    {
        $product = $this->catalogManager->getProductById($id, $request->getLocale());

        return $this->productDetails($request, $product, $user);
    }

    private function productDetails(Request $request, $product, UserInterface $user = null)
    {
        $variantIds = [];

        foreach ($product->getAllVariants() as $variant) {
            $variantIds[$variant->getSku()] = $variant->getId();
        }

        $shoppingListsIds = [];
        if(is_null($user)){
            $shoppingLists = $this->shoppingListManager->getAllOfAnonymous($request->getLocale(), $this->get('session')->getId());
        } else {
            $shoppingLists = $this->shoppingListManager->getAllOfCustomer($request->getLocale(), CustomerReference::ofId($user->getId()));
        }

        foreach ($shoppingLists as $shoppingList) {
            /** @var ShoppingList $shoppingList */
            $shoppingListsIds[(string)$shoppingList->getName()] = $shoppingList->getId();
        }

        $data = [
            '_productId' => $product->getId(),
            'variantId' => 1,
            'slug' => (string)$product->getSlug(),
            'variant_choices' => $variantIds,
            'shopping_lists' => $shoppingListsIds
        ];

        $form = $this->createForm(AddToCartType::class, $data, ['action' => $this->generateUrl('_ctp_example_add_lineItem')]);
        $form->handleRequest($request);

        $shoppingListForm = $this->createForm(AddToShoppingListType::class, $data, ['action' => $this->generateUrl('_ctp_example_shoppingList_add_lineItem')]);
        $shoppingListForm->handleRequest($request);

        return $this->render('ExampleBundle:catalog:product.html.twig', [
            'product' =>  $product,
            'form' => $form->createView(),
            'shoppingListForm' => $shoppingListForm->createView()
        ]);
    }

    public function suggestAction(Request $request, $searchTerm)
    {
        $products = $this->catalogManager->suggestProducts($request->getLocale(), $searchTerm, 5, 'EUR', 'DE');

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
}
