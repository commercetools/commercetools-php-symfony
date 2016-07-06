<?php

namespace  Commercetools\Symfony\CtpBundle\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Symfony\CtpBundle\Model\Form\Type\AddToCartType;
use Commercetools\Symfony\CtpBundle\Model\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Valid;

class CatalogController extends Controller
{
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
        if ($form->isValid() && $form->isSubmitted() ){
            $search = $form->get('search')->getData();
        }

        list($products, $offset) = $repository->getProducts($request->getLocale(), 12, 1, 'price asc', 'EUR', 'DE', $search);

        return $this->render('CtpBundle:catalog:index.html.twig', [
                'products' => $products,
                'form' => $form->createView(),
        ]);

    }

    public function detailAction(Request $request, $slug)
    {
        /**
         * @var ProductRepository $repository
         */
        $repository = $this->get('commercetools.repository.product');

        $product = $repository->getProductBySlug($slug, $request->getLocale());

        $variantIds = [];

        foreach ($product->getAllVariants() as $variant) {
            $variantIds[$variant->getSku()] = $variant->getId();
        }

        $data = [
            'productId' => $product->getId(),
            'variantId' => 1,
            'slug' => (string)$product->getSlug(),
            'variant_choices' => $variantIds
        ];
        $form = $this->createForm(AddToCartType::class, $data, ['action' => $this->generateUrl('_ctp_example_add_lineItem')]);
        $form->handleRequest($request);
        return $this->render('CtpBundle:catalog:product.html.twig', [
                'product' =>  $product,
                'form' => $form->createView()
        ]);
    }

    public function suggestAction(Request $request, $searchTerm)
    {
//        $searchTerm = null;
        $repository = $this->get('commercetools.repository.product');
        list($products, $offset) = $repository->getProducts($request->getLocale(), 5, 1, 'price asc', 'EUR', 'DE', $searchTerm);

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
