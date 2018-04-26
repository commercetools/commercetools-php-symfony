<?php
declare(strict_types=1);

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListAddLineItemAction;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToShoppingListType;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;


class ShoppingListController extends Controller
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ShoppingListManager
     */
    private $manager;

    /**
     * ShoppingListController constructor.
     */
    public function __construct(Client $client, ShoppingListManager $manager)
    {
        $this->client = $client;
        $this->manager = $manager;
    }

    public function indexAction(Request $request, UserInterface $user)
    {
        $params = new QueryParams();
        $params->add('expand', 'lineItems[*].variant');
        $shoppingLists = $this->manager->getAllOfCustomer($request->getLocale(), CustomerReference::ofId($user->getId()), $params);

        return $this->render('ExampleBundle:shoppinglist:index.html.twig', ['lists' => $shoppingLists]);
    }

    public function create(Request $request, UserInterface $user)
    {
        $this->manager->createShoppingList($request->getLocale(), CustomerReference::ofId($user->getId()), $request->request->get('_name'));

        return $this->redirectToRoute('shopping_list_index');
    }

    public function addLineItemAction(Request $request, UserInterface $user)
    {
        $shoppingLists = $this->manager->getAllOfCustomer($request->getLocale(), CustomerReference::ofId($user->getId()));
        $shoppingListsIds = [];

        foreach ($shoppingLists as $shoppingList) {
            /** @var ShoppingList $shoppingList */
            $shoppingListsIds[(string)$shoppingList->getName()] = $shoppingList->getId();
        }

        $data = [
            'variantIdText' => true,
            'shopping_lists' => $shoppingListsIds
        ];

        $form = $this->createForm(AddToShoppingListType::class, $data);
        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $shoppingList = $this->manager->getById($request->getLocale(), $form->get('_shoppingListId')->getData());
            $updateBuilder = $this->manager->update($shoppingList);
            $updateBuilder->addLineItem(function (ShoppingListAddLineItemAction $action) use($form): ShoppingListAddLineItemAction {
                $action->setProductId($form->get('_productId')->getData());
                $action->setVariantId((int)$form->get('_variantId')->getData());
                $action->setQuantity(1);
                return $action;
            });

            $updateBuilder->flush();
        }

        return $this->redirectToRoute('shopping_list_index');
    }

    public function removeLineItem(Request $request)
    {
        $this->manager->removeLineItem(
            $this->manager->getById($request->getLocale(), $request->get('_shoppingListId')),
            $request->request->get('_lineItemId')
        );

        return $this->redirectToRoute('shopping_list_index');
    }

    public function changeLineItemQuantity(Request $request)
    {
        $this->manager->changeLineItemQuantity(
            $this->manager->getById($request->getLocale(), $request->get('_shoppingListId')),
            $request->request->get('_lineItemId'),
            (int)$request->request->get('_lineItemQuantity')
        );

        return $this->redirectToRoute('shopping_list_index');
    }

}
