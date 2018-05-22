<?php

namespace  Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToCartType;
use Commercetools\Symfony\CtpBundle\Model\Repository\ProductRepository;
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
    /**
     * CatalogController constructor.
     */
    public function __construct(Client $client, ShoppingListManager $manager)
    {
        $this->client = $client;
        $this->manager = $manager;
    }
    public function indexAction(Request $request)
    {
        /**
         * @var ProductRepository $repository
         */
        $repository = $this->get('commercetools.repository.product');

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
        list($products, $offset) = $repository->getProducts(
            $request->getLocale(), 12, 1, 'price asc', 'EUR', 'DE', $search, $uri
        );

        return $this->render('ExampleBundle:catalog:index.html.twig', [
                'products' => $products,
                'form' => $form->createView(),
        ]);

    }

    public function detailBySlugAction(Request $request, $slug, UserInterface $user = null)
    {
        /**
         * @var ProductRepository $repository
         */
        $repository = $this->get('commercetools.repository.product');

        $product = $repository->getProductBySlug($slug, $request->getLocale(), 'EUR', 'DE');

        $variantIds = [];

        foreach ($product->getAllVariants() as $variant) {
            $variantIds[$variant->getSku()] = $variant->getId();
        }

        $shoppingListsIds = [];
        if(is_null($user)){
            $shoppingLists = $this->manager->getAllOfAnonymous($request->getLocale(), $this->get('session')->getId());
        } else {
            $shoppingLists = $this->manager->getAllOfCustomer($request->getLocale(), CustomerReference::ofId($user->getId()));
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

        $shoppingListForm = $this->createForm(AddToShoppingListType::class, $data, ['action' => $this->generateUrl('_ctp_example_add_lineItem_to_shoppingList')]);
        $shoppingListForm->handleRequest($request);

        return $this->render('ExampleBundle:catalog:product.html.twig', [
                'product' =>  $product,
                'form' => $form->createView(),
                'shoppingListForm' => $shoppingListForm->createView()
        ]);
    }

    public function suggestAction(Request $request, $searchTerm)
    {
//        $searchTerm = null;
        $repository = $this->get('commercetools.repository.product');
        $products = $repository->suggestProducts($request->getLocale(), $searchTerm, 5, 'EUR', 'DE');

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
