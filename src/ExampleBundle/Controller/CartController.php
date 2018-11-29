<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Cart\LineItemDraft;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\ShoppingList\ShoppingListReference;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Core\Request\Carts\Command\CartAddLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartAddShoppingListAction;
use Commercetools\Core\Request\Carts\Command\CartChangeLineItemQuantityAction;
use Commercetools\Core\Request\Carts\Command\CartRemoveLineItemAction;
use Commercetools\Symfony\ExampleBundle\Entity\ProductEntity;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToCartType;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Client;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;


class CartController extends AbstractController
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

    public function indexAction(Request $request, SessionInterface $session, UserInterface $user = null)
    {
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->manager->getCart($request->getLocale(), $cartId, $user, $session->getId());

        if (is_null($cart)) {
            $cart = Cart::of();
        }

        return $this->render('ExampleBundle:cart:index.html.twig', [
            'cart' => $cart
        ]);
    }

    public function addLineItemAction(Request $request, SessionInterface $session, UserInterface $user = null)
    {
        $productEntity = new ProductEntity();

        $form = $this->createForm(AddToCartType::class, $productEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $productId = $form->get('productId')->getData();
            $variantId = (int)$form->get('variantId')->getData();
            $quantity = (int)$form->get('quantity')->getData();
            $slug = $form->get('slug')->getData();

            $cartId = $session->get(CartRepository::CART_ID);

            if(!is_null($cartId)){
                $cart = $this->manager->getCart($request->getLocale(), $cartId, $user, $session->getId());

                $cartBuilder = $this->manager->update($cart);
                $cartBuilder->addAction(
                    CartAddLineItemAction::ofProductIdVariantIdAndQuantity($productId, $variantId, $quantity)
                );
                $cartBuilder->flush();

            } else {
                $lineItem = LineItemDraft::ofProductId($productId)->setVariantId($variantId)->setQuantity($quantity);
                $lineItemDraftCollection = LineItemDraftCollection::of()->add($lineItem);

                $countryCode = $this->getCountryFromConfig();
                $currency = $this->getCurrencyFromConfig();
                $country = Location::of()->setCountry($countryCode);

                if(is_null($user)){
                    $this->manager->createCartForUser($request->getLocale(), $currency, $country, $lineItemDraftCollection, null, $session->getId());
                } else {
                    $this->manager->createCartForUser($request->getLocale(), $currency, $country, $lineItemDraftCollection, $user->getID());
                }
            }
            $redirectUrl = $this->generateUrl('_ctp_example_product', ['slug' => $slug]);

        } else {
            $redirectUrl = $this->generateUrl('_ctp_example');
        }

        return new RedirectResponse($redirectUrl);
    }

//    public function miniCartAction(Request $request)
//    {
//        $response = new Response();
//        $response->headers->addCacheControlDirective('no-cache');
//        $response->headers->addCacheControlDirective('no-store');
//
//        $response = $this->render('ExampleBundle:cart:index.html.twig', $response);
//
//        return $response;
//    }

    public function changeLineItemAction(Request $request, SessionInterface $session, UserInterface $user = null)
    {
        $lineItemId = $request->get('lineItemId');
        $quantity = (int)$request->get('quantity');
        $cartId = $session->get(CartRepository::CART_ID);

        $cart = $this->manager->getCart($request->getLocale(), $cartId, $user, $session->getId());

        $cartBuilder = $this->manager->update($cart);
        $cartBuilder->addAction(
            CartChangeLineItemQuantityAction::ofLineItemIdAndQuantity($lineItemId, $quantity)
        );
        $cartBuilder->flush();

        return new RedirectResponse($this->generateUrl('_ctp_example_cart'));
    }

    public function deleteLineItemAction(Request $request, SessionInterface $session, UserInterface $user = null)
    {
        $lineItemId = $request->get('lineItemId');
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->manager->getCart($request->getLocale(), $cartId, $user, $session->getId());

        $cartBuilder = $this->manager->update($cart);
        $cartBuilder->addAction(CartRemoveLineItemAction::ofLineItemId($lineItemId));

        $cartBuilder->flush();

        return new RedirectResponse($this->generateUrl('_ctp_example_cart'));
    }

    public function addShoppingListToCartAction(Request $request, SessionInterface $session, UserInterface $user = null)
    {
        $cartId = $session->get(CartRepository::CART_ID);

        $shoppingListId = $request->get('shoppingListId');
        $shoppingList = ShoppingListReference::ofId($shoppingListId);

        if(!is_null($cartId)){
            $cart = $this->manager->getCart($request->getLocale(), $cartId, $user, $session->getId());
        } else {
            $countryCode = $this->getCountryFromConfig();
            $currency = $this->getCurrencyFromConfig();
            $country = Location::of()->setCountry(strtoupper($countryCode));

            if(is_null($user)){
                $cart = $this->manager->createCartForUser($request->getLocale(), $currency, $country, null, null, $session->getId());
            } else {
                $cart = $this->manager->createCartForUser($request->getLocale(), $currency, $country, null, $user->getId());
            }
        }

        $cartBuilder = $this->manager->update($cart);
        $cartBuilder->addShoppingList(CartAddShoppingListAction::ofShoppingList($shoppingList));
        $cartBuilder->flush();

        return new RedirectResponse($this->generateUrl('_ctp_example_cart'));
    }

//    protected function getItemCount(Cart $cart)
//    {
//        $count = 0;
//        if ($cart->getLineItems()) {
//            foreach ($cart->getLineItems() as $lineItem) {
//                $count+= $lineItem->getQuantity();
//            }
//        }
//        return $count;
//    }

    /**
     * Creates and returns a form builder instance.
     *
     * @param $name
     * @param mixed $data The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormBuilder
     */
//    protected function createNamedFormBuilder($name, $data = null, array $options = array())
//    {
//        return $this->container->get('form.factory')->createNamedBuilder($name, FormType::class, $data, $options);
//    }

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
