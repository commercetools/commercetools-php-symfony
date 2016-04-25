<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Controller;

use Commercetools\Core\Model\Common\Address;
use Commercetools\Symfony\CtpBundle\Entity\BillingAddress;
use Commercetools\Symfony\CtpBundle\Entity\CartEntity;
use Commercetools\Symfony\CtpBundle\Entity\Checkout;
use Commercetools\Symfony\CtpBundle\Entity\ShippingAddress;
use Commercetools\Symfony\CtpBundle\Model\Form\Type\AddressType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Tests\Fixtures\Entity;

class CheckoutController extends Controller
{
    public function signinAction(Request $request)
    {
        return new Response('blub');
    }

    public function setAddressAction(Request $request)
    {
        $session = $this->get('session');
        $cartId = $session->get('cartId');
        $cart = $this->get('commercetools.repository.cart')->getCart($request->getLocale(), $cartId);

        $lineItems = $cart->getLineItems()->current();
        $variantId = $lineItems->getVariant()->getId();
        $productId = $lineItems->getProductId();
        $country = $cart->getCountry();
        $quantity = $lineItems->getQuantity();
        $currency = $lineItems->getPrice()->getValue()->getCurrencyCode();

        $entity = CartEntity::ofCart($cart);
        $form = $this->createFormBuilder($entity)
            ->add('check', CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'Shipping and Billing addresses are the same ',
                    'empty_data' => NULL
                ])
            ->add('shippingAddress', AddressType::class)
            ->add('billingAddress', AddressType::class)
            ->add('Submit', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);

        $repository = $this->get('commercetools.repository.cart');

        $check = $form->get('check')->getData();

        if ($form->isValid() && $form->isSubmitted()) {
            $shippingAddress = Address::fromArray($form->get('shippingAddress')->getData());
            $billingAddress = null;

            if($check !== true) {
                $billingAddress = Address::fromArray($form->get('billingAddress')->getData());
            }

            $checkout = $repository->setAddresses(
                $request->getLocale(),
                $cartId,
                $shippingAddress,
                $billingAddress
            );
            var_dump($cartId);
        }

        return $this->render('CtpBundle:cart:checkout.html.twig',
        [
            'form' => $form->createView(),
        ]);
    }
}

