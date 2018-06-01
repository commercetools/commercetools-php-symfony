<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Cart\LineItemDraft;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Core\Request\Carts\Command\CartAddLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartChangeLineItemQuantityAction;
use Commercetools\Core\Request\Carts\Command\CartRemoveLineItemAction;
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
use Symfony\Component\Security\Core\User\UserInterface;


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

    public function addLineItemAction(Request $request, UserInterface $user = null)
    {
        $session = $this->get('session');

        $form = $this->createForm(AddToCartType::class, ['variantIdText' => true]);
        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {

            $productId = $form->get('_productId')->getData();
            $variantId = (int)$form->get('variantId')->getData();
            $quantity = (int)$form->get('quantity')->getData();
            $slug = $form->get('slug')->getData();

            $cartId = $session->get(CartRepository::CART_ID);

            if(!is_null($cartId)){
                $cart = $this->manager->getCart($request->getLocale(), $cartId, $this->getCustomerId());
                $cartBuilder = $this->manager->update($cart);
                $cartBuilder->addAction(CartAddLineItemAction::ofProductIdVariantIdAndQuantity($productId, $variantId, $quantity));
                $cartBuilder->flush();

            } else {
                $lineItem = LineItemDraft::ofProductId($productId)->setVariantId($variantId)->setQuantity($quantity);
                $lineItemDraftCollection = LineItemDraftCollection::of()->add($lineItem);
                $country = Location::of()->setCountry('DE');
                if(is_null($user)){
                    $this->manager->createCart($request->getLocale(), 'EUR', $country, $lineItemDraftCollection, null, $session->getId());
                } else {
                    $this->manager->createCart($request->getLocale(), 'EUR', $country, $lineItemDraftCollection, $user->getID());
                }
            }
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
        $quantity = (int)$request->get('quantity');
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->manager->getCart($request->getLocale(), $cartId, $this->getCustomerId());

        $cartBuilder = $this->manager->update($cart);
        $cartBuilder->addAction(CartChangeLineItemQuantityAction::ofLineItemIdAndQuantity($lineItemId, $quantity));
        $cartBuilder->flush();

        return new RedirectResponse($this->generateUrl('_ctp_example_cart'));
    }

    public function deleteLineItemAction(Request $request)
    {
        $session = $this->get('session');
        $lineItemId = $request->get('lineItemId');
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->manager->getCart($request->getLocale(), $cartId, $this->getCustomerId());

        $cartBuilder = $this->manager->update($cart);
        $cartBuilder->addAction(CartRemoveLineItemAction::ofLineItemId($lineItemId));
        $cartBuilder->flush();

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
     * @param $name
     * @param mixed $data The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormBuilder
     */
    protected function createNamedFormBuilder($name, $data = null, array $options = array())
    {
        return $this->container->get('form.factory')->createNamedBuilder($name, FormType::class, $data, $options);
    }


}
