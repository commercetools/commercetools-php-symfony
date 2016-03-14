<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Common\Money;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class CartController extends Controller
{
    const CSRF_TOKEN_NAME = 'csrfToken';

    public function indexAction(Request $request)
    {
        $session = $this->get('session');
        $cartId = $session->get('cartId');
        $cart = $this->get('commercetools.repository.cart')->getCart($cartId);

        return $this->render('CtpBundle:catalog:cart.html.twig', ['cart' => $cart]);
    }

    public function addAction(Request $request)
    {
        $session = $this->get('session');

        $productId = $request->get('productId');
        $variantId = (int)$request->get('variantId');
        $quantity = (int)$request->get('quantity');
        $sku = $request->get('productSku');
        $slug = $request->get('productSlug');
        $cartId = $session->get('cartId');
        $country = \Locale::getRegion($this->locale);
        $currency = $this->get('commercetools.currency.'. $country);
        $cart = $this->get('commercetools.repository.cart')
            ->addLineItem($cartId, $productId, $variantId, $quantity, $currency, $country);
        $session->set('cartId', $cart->getId());
        $session->set('cartNumItems', $this->getItemCount($cart));
        $session->save();

        if (empty($sku)) {
            $redirectUrl = $this->generateUrl('pdp-master', ['slug' => $slug]);
        } else {
            $redirectUrl = $this->generateUrl('pdp', ['slug' => $slug, 'sku' => $sku]);
        }
        return new RedirectResponse($redirectUrl);
    }

    public function miniCartAction(Request $request)
    {
        $response = new Response();
        $response->headers->addCacheControlDirective('no-cache');
        $response->headers->addCacheControlDirective('no-store');

        $response = $this->render('common/mini-cart.hbs', $response);

        return $response;
    }

    public function changeLineItemAction(Request $request)
    {
        $session = $this->get('session');
        $lineItemId = $request->get('lineItemId');
        $lineItemCount = (int)$request->get('quantity');
        $cartId = $session->get('cartId');
        $cart = $this->get('commercetools.repository.cart')
            ->changeLineItemQuantity($cartId, $lineItemId, $lineItemCount);

        $session->set('cartNumItems', $this->getItemCount($cart));
        $session->save();

        return new RedirectResponse($this->generateUrl('cart'));
    }

    public function deleteLineItemAction(Request $request)
    {
        $session = $this->get('session');
        $lineItemId = $request->get('lineItemId');
        $cartId = $session->get('cartId');
        $cart = $this->get('commercetools.repository.cart')->deleteLineItem($cartId, $lineItemId);

        $session->set('cartNumItems', $this->getItemCount($cart));
        $session->save();

        return new RedirectResponse($this->generateUrl('cart'));
    }

//    public function checkoutAction(Request $request)
//    {
//        $session = $this->get('session');
//        $userId = $session->get('userId');
//        if (is_null($userId)) {
//            return $this->checkoutSigninAction($request);
//        }
//
//        return $this->checkoutShippingAction($request);
//    }

//    public function checkoutSigninAction(Request $request)
//    {
//        $viewData = $this->getViewData('Sunrise - Checkout - Signin', $request);
//        return $this->render('checkout-signin.hbs', $viewData->toArray());
//    }
//
//    public function checkoutShippingAction(Request $request)
//    {
//        $viewData = $this->getViewData('Sunrise - Checkout - Shipping', $request);
//        return $this->render('checkout-shipping.hbs', $viewData->toArray());
//    }
//
//    public function checkoutPaymentAction(Request $request)
//    {
//        $viewData = $this->getViewData('Sunrise - Checkout - Payment', $request);
//        return $this->render('checkout-payment.hbs', $viewData->toArray());
//    }
//
//    public function checkoutConfirmationAction(Request $request)
//    {
//        $viewData = $this->getViewData('Sunrise - Checkout - Confirmation', $request);
//        return $this->render('checkout-confirmation.hbs', $viewData->toArray());
//    }
//
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

    protected function getCart(Cart $cart)
    {
        $cartModel = new ViewData();
        $cartModel->totalItems = $this->getItemCount($cart);
        if ($cart->getTaxedPrice()) {
            $salexTax = Money::ofCurrencyAndAmount(
                $cart->getTaxedPrice()->getTotalGross()->getCurrencyCode(),
                $cart->getTaxedPrice()->getTotalGross()->getCentAmount() -
                $cart->getTaxedPrice()->getTotalNet()->getCentAmount(),
                $cart->getContext()
            );
            $cartModel->salesTax = (string)$salexTax;
            $cartModel->subtotalPrice = (string)$cart->getTaxedPrice()->getTotalNet();
            $cartModel->totalPrice = (string)$cart->getTotalPrice();
        }
        if ($cart->getShippingInfo()) {
            $shippingInfo = $cart->getShippingInfo();
            $cartModel->shippingMethod = new ViewData();
            $cartModel->shippingMethod->price = (string)$shippingInfo->getPrice();
        }

        $cartModel->lineItems = $this->getCartLineItems($cart);
        return $cartModel;
    }

    protected function getCartLineItems(Cart $cart)
    {
        $cartItems = new ViewData();
        $cartItems->list = new ViewDataCollection();

        $lineItems = $cart->getLineItems();

        if (!is_null($lineItems)) {
            foreach ($lineItems as $lineItem) {
                $variant = $lineItem->getVariant();
                $cartLineItem = new ViewData();
                $cartLineItem->productId = $lineItem->getProductId();
                $cartLineItem->variantId = $variant->getId();
                $cartLineItem->lineItemId = $lineItem->getId();
                $cartLineItem->quantity = $lineItem->getQuantity();
                $lineItemVariant = new ViewData();
                $lineItemVariant->url = (string)$this->generateUrl(
                    'pdp-master',
                    ['slug' => (string)$lineItem->getProductSlug()]
                );
                $lineItemVariant->name = (string)$lineItem->getName();
                $lineItemVariant->image = (string)$variant->getImages()->current()->getUrl();
                $price = $lineItem->getPrice();
                if (!is_null($price->getDiscounted())) {
                    $lineItemVariant->price = (string)$price->getDiscounted()->getValue();
                    $lineItemVariant->priceOld = (string)$price->getValue();
                } else {
                    $lineItemVariant->price = (string)$price->getValue();
                }
                $cartLineItem->variant = $lineItemVariant;
                $cartLineItem->sku = $variant->getSku();
                $cartLineItem->totalPrice = $lineItem->getTotalPrice();
                $cartLineItem->attributes = new ViewDataCollection();

                $cartAttributes = $this->get('commercetools.cart.attributes');
                foreach ($cartAttributes as $attributeName) {
                    $attribute = $variant->getAttributes()->getByName($attributeName);
                    if ($attribute) {
                        $lineItemAttribute = new ViewData();
                        $lineItemAttribute->label = $attributeName;
                        $lineItemAttribute->key = $attributeName;
                        $lineItemAttribute->value = (string)$attribute->getValue();
                        $cartLineItem->attributes->add($lineItemAttribute);
                    }
                }
                $cartItems->list->add($cartLineItem);
            }
        }

        return $cartItems;
    }
}