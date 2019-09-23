<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Cart\LineItemDraft;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Cart\MyLineItemDraft;
use Commercetools\Core\Model\Cart\MyLineItemDraftCollection;
use Commercetools\Core\Model\ShoppingList\ShoppingListReference;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Core\Request\Carts\Command\CartAddLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartAddShoppingListAction;
use Commercetools\Core\Request\Carts\Command\CartChangeLineItemQuantityAction;
use Commercetools\Core\Request\Carts\Command\CartRemoveLineItemAction;
use Commercetools\Symfony\CartBundle\Manager\MeCartManager;
use Commercetools\Symfony\ExampleBundle\Entity\ProductEntity;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToCartType;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CartController extends AbstractController
{
    const CSRF_TOKEN_NAME = 'csrfToken';


    /**
     * @var CartManager
     */
    private $manager;

    /**
     * @var MeCartManager $meCartManager
     */
    private $meCartManager;

    /**
     * CartController constructor.
     * @param CartManager $manager
     * @param MeCartManager $meCartManager
     */
    public function __construct(CartManager $manager, MeCartManager $meCartManager)
    {
        $this->manager = $manager;
        $this->meCartManager = $meCartManager;
    }

    public function indexAction(Request $request)
    {
        $cart = $this->meCartManager->getCart($request->getLocale()) ?? Cart::of();

        return $this->render('@Example/cart.html.twig', [
            'cart' => $cart
        ]);
    }

    public function addLineItemAction(Request $request)
    {
        $productEntity = new ProductEntity();

        $form = $this->createForm(AddToCartType::class, $productEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productId = $form->get('productId')->getData();
            $variantId = (int)$form->get('variantId')->getData();
            $quantity = (int)$form->get('quantity')->getData();
            $slug = $form->get('slug')->getData();

            $cart = $this->meCartManager->getCart($request->getLocale());

            if ($cart instanceof Cart) {
                $cartBuilder = $this->meCartManager->update($cart);
                $cartBuilder->addAction(
                    CartAddLineItemAction::ofProductIdVariantIdAndQuantity($productId, $variantId, $quantity)
                );
                $cartBuilder->flush();
            } else {
                $lineItem = MyLineItemDraft::of()->setProductId($productId)->setVariantId($variantId)->setQuantity($quantity);
                $lineItemDraftCollection = MyLineItemDraftCollection::of()->add($lineItem);

                $countryCode = $this->getCountryFromConfig();
                $currency = $this->getCurrencyFromConfig();
                $location = Location::of()->setCountry($countryCode);

                $this->meCartManager->createCart($request->getLocale(), $currency, $location, $lineItemDraftCollection);
            }
            $redirectUrl = $this->generateUrl('_ctp_example_product', ['slug' => $slug]);
        } else {
            $redirectUrl = $this->generateUrl('_ctp_example_index');
        }

        return new RedirectResponse($redirectUrl);
    }

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

        if (!is_null($cartId)) {
            $cart = $this->manager->getCart($request->getLocale(), $cartId, $user, $session->getId());
        } else {
            $countryCode = $this->getCountryFromConfig();
            $currency = $this->getCurrencyFromConfig();
            $location = Location::of()->setCountry(strtoupper($countryCode));

            if (is_null($user)) {
                $cart = $this->manager->createCartForUser($request->getLocale(), $currency, $location, null, null, $session->getId());
            } else {
                $cart = $this->manager->createCartForUser($request->getLocale(), $currency, $location, null, $user->getId());
            }
        }

        $cartBuilder = $this->manager->update($cart);
        $cartBuilder->addShoppingList(CartAddShoppingListAction::ofShoppingList($shoppingList));
        $cartBuilder->flush();

        return new RedirectResponse($this->generateUrl('_ctp_example_cart'));
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
