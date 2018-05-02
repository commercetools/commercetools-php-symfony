<?php
/**
 * @author: NikosSo <nikolaos.sotiropoulos@commercetools.de>
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToCartType;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Client;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class CartController extends Controller
{
    const CSRF_TOKEN_NAME = 'csrfToken';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var CartManager
     */
    private $manager;

    /**
     * CartController constructor.
     */
    public function __construct(Client $client, CartManager $manager)
    {
        $this->client = $client;
        $this->manager = $manager;
    }


    protected function getCustomerId()
    {
        $user = $this->getUser();
        if (is_null($user)) {
            return null;
        }
        $customerId = $user->getId();

        return $customerId;
    }

    public function indexAction(Request $request)
    {
        $session = $this->get('session');
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->manager->getCart($request->getLocale(), $cartId, $this->getCustomerId());

        $form = $this->createNamedFormBuilder('')
            ->add('lineItemId', TextType::class)
            ->add('quantity', TextType::class)
            ->getForm();

        return $this->render('ExampleBundle:cart:index.html.twig', ['cart' => $cart]);
    }

    public function addLineItemAction(Request $request)
    {
        $locale = $this->get('commercetools.locale.converter')->convert($request->getLocale());
        $session = $this->get('session');

        $form = $this->createForm(AddToCartType::class, ['variantIdText' => true]);
        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $productId = $form->get('productId')->getData();
            $variantId = (int)$form->get('variantId')->getData();
            $quantity = (int)$form->get('quantity')->getData();
            $slug = $form->get('slug')->getData();
            $cartId = $session->get(CartRepository::CART_ID);
            $country = \Locale::getRegion($locale);
            $currency = $this->getParameter('commercetools.currency.'. $country);

            /**
             * @var CartRepository $repository
             */
            $repository = $this->get('commercetools.repository.cart');
            $repository->addLineItem(
                $request->getLocale(),
                $cartId,
                $productId,
                $variantId,
                $quantity,
                $currency,
                $country,
                $this->getCustomerId()
            );
            $redirectUrl = $this->generateUrl('_ctp_example_product', ['slug' => $slug]);
        } else {
            $redirectUrl = $this->generateUrl('_ctp_example');
        }

        return new RedirectResponse($redirectUrl);
    }

    public function miniCartAction(Request $request)
    {
        $response = new Response();
        $response->headers->addCacheControlDirective('no-cache');
        $response->headers->addCacheControlDirective('no-store');

        $response = $this->render('ExampleBundle:cart:index.html.twig', $response);

        return $response;
    }

    public function changeLineItemAction(Request $request)
    {
        $session = $this->get('session');
        $lineItemId = $request->get('lineItemId');
        $lineItemCount = (int)$request->get('quantity');
        $cartId = $session->get(CartRepository::CART_ID);
        /**
         * @var CartRepository $repository
         */
        $repository = $this->get('commercetools.repository.cart');
        $repository->changeLineItemQuantity($request->getLocale(), $cartId, $lineItemId, $lineItemCount, $this->getCustomerId());

        return new RedirectResponse($this->generateUrl('_ctp_example_cart'));
    }

    public function deleteLineItemAction(Request $request)
    {
        $session = $this->get('session');
        $lineItemId = $request->get('lineItemId');
        $cartId = $session->get(CartRepository::CART_ID);
        $this->get('commercetools.repository.cart')->deleteLineItem($request->getLocale(), $cartId, $lineItemId, $this->getCustomerId());

        return new RedirectResponse($this->generateUrl('_ctp_example_cart'));
    }

    protected function getItemCount(Cart $cart)
    {
        $count = 0;
        if ($cart->getLineItems()) {
            foreach ($cart->getLineItems() as $lineItem) {
                $count+= $lineItem->getQuantity();
            }
        }
        return $count;
    }

    /**
     * Creates and returns a form builder instance.
     *
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormBuilder
     */
    protected function createNamedFormBuilder($name, $data = null, array $options = array())
    {
        return $this->container->get('form.factory')->createNamedBuilder($name, FormType::class, $data, $options);
    }
}
