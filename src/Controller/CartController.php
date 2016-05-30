<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Controller;

use Commercetools\Symfony\CtpBundle\Model\Form\Type\AddToCartType;
use Commercetools\Symfony\CtpBundle\Model\Repository\CartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Common\Money;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class CartController extends Controller
{
    const CSRF_TOKEN_NAME = 'csrfToken';

    public function indexAction(Request $request)
    {
        $session = $this->get('session');
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->get('commercetools.repository.cart')->getCart($request->getLocale(), $cartId);

        $form = $this->createNamedFormBuilder('')
            ->add('lineItemId', TextType::class)
            ->add('quantity', TextType::class)
            ->getForm();

        return $this->render('CtpBundle:cart:index.html.twig', ['cart' => $cart]);
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
            $repository->addLineItem($request->getLocale(), $cartId, $productId, $variantId, $quantity, $currency, $country);
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

        $response = $this->render('CtpBundle:cart:index.html.twig', $response);

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
        $repository->changeLineItemQuantity($request->getLocale(), $cartId, $lineItemId, $lineItemCount);

        return new RedirectResponse($this->generateUrl('_ctp_example_cart'));
    }

    public function deleteLineItemAction(Request $request)
    {
        $session = $this->get('session');
        $lineItemId = $request->get('lineItemId');
        $cartId = $session->get(CartRepository::CART_ID);
        $this->get('commercetools.repository.cart')->deleteLineItem($request->getLocale(), $cartId, $lineItemId);

        return new RedirectResponse($this->generateUrl('_ctp_example_cart'));
    }


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
