<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Commercetools\Core\Model\Cart\Cart;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class CartController extends AbstractController
{
    /**
     * @var MeCartManager $meCartManager
     */
    private $meCartManager;

    /**
     * CartController constructor.
     * @param MeCartManager $meCartManager
     */
    public function __construct(MeCartManager $meCartManager)
    {
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

    public function changeLineItemAction(Request $request)
    {
        $lineItemId = $request->get('lineItemId');
        $quantity = (int)$request->get('quantity');

        $cart = $this->meCartManager->getCart($request->getLocale());

        $cartBuilder = $this->meCartManager->update($cart);
        $cartBuilder->addAction(
            CartChangeLineItemQuantityAction::ofLineItemIdAndQuantity($lineItemId, $quantity)
        );
        $cartBuilder->flush();

        return new RedirectResponse($this->generateUrl('_ctp_example_cart'));
    }

    public function deleteLineItemAction(Request $request)
    {
        $lineItemId = $request->get('lineItemId');
        $cart = $this->meCartManager->getCart($request->getLocale());

        $cartBuilder = $this->meCartManager->update($cart);
        $cartBuilder->addAction(CartRemoveLineItemAction::ofLineItemId($lineItemId));

        $cartBuilder->flush();

        return new RedirectResponse($this->generateUrl('_ctp_example_cart'));
    }

    public function addShoppingListToCartAction(Request $request)
    {
        $shoppingListId = $request->get('shoppingListId');
        $shoppingList = ShoppingListReference::ofId($shoppingListId);
        $locale = $request->getLocale();
        $cart = $this->meCartManager->getCart($locale);
        if (is_null($cart)) {
            $countryCode = $this->getCountryFromConfig();
            $currency = $this->getCurrencyFromConfig();
            $location = Location::of()->setCountry(strtoupper($countryCode));

            $cart = $this->meCartManager->createCart($locale, $currency, $location);
        }

        $cartBuilder = $this->meCartManager->update($cart);
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
