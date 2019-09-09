<?php
declare(strict_types=1);

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListAddLineItemAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeLineItemQuantityAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListRemoveLineItemAction;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\ExampleBundle\Entity\ProductToShoppingList;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddToShoppingListType;
use Commercetools\Symfony\ShoppingListBundle\Manager\MeShoppingListManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ShoppingListController extends AbstractController
{
    /**
     * @var MeShoppingListManager
     */
    private $manager;

    /**
     * ShoppingListController constructor.
     * @param MeShoppingListManager $manager
     */
    public function __construct(MeShoppingListManager $manager)
    {
        $this->manager = $manager;
    }

    public function indexAction(Request $request)
    {
        $params = new QueryParams();
        $params->add('expand', 'lineItems[*].variant');
        $params->add('sort', 'createdAt desc');

        $shoppingLists = $this->manager->getAllMyShoppingLists($request->getLocale(), $params);

        return $this->render('@Example/my-account-wishlist.html.twig', ['lists' => $shoppingLists]);
    }

    public function createAction(Request $request)
    {
        $this->manager->createShoppingList($request->getLocale(), $request->get('shoppingListName'));

        return $this->redirectToRoute('_ctp_example_shoppingList');
    }

    public function deleteByIdAction(Request $request, $shoppingListId)
    {
        $shoppingList = $this->manager->getById($request->getLocale(), $shoppingListId);

        $this->manager->deleteShoppingList($request->getLocale(), $shoppingList);

        return new RedirectResponse($this->generateUrl('_ctp_example_cart'));
    }

    public function addLineItemAction(Request $request)
    {
        $shoppingListsIds = [];
        $shoppingLists = $this->manager->getAllMyShoppingLists($request->getLocale());

        foreach ($shoppingLists as $shoppingList) {
            /** @var ShoppingList $shoppingList */
            $shoppingListsIds[(string)$shoppingList->getName()] = $shoppingList->getId();
        }

        $productEntity = new ProductToShoppingList();
        $productEntity->setAvailableShoppingLists($shoppingListsIds);

        $form = $this->createForm(AddToShoppingListType::class, $productEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $shoppingListId = $form->get('shoppingListId')->getData();

            if (!is_null($shoppingListId)) {
                $shoppingList = $this->manager->getById($request->getLocale(), $shoppingListId);
                $updateBuilder = $this->manager->update($shoppingList);
                $updateBuilder->addLineItem(function (ShoppingListAddLineItemAction $action) use ($form): ShoppingListAddLineItemAction {
                    $action->setProductId($form->get('productId')->getData());
                    $action->setVariantId((int)$form->get('variantId')->getData());
                    $action->setQuantity(1);
                    return $action;
                });

                $updateBuilder->flush();
            } else {
                $this->addFlash('error', 'Not valid shopping list provided');
            }
        }

        return $this->redirectToRoute('_ctp_example_shoppingList');
    }

    public function removeLineItemAction(Request $request)
    {
        $shoppingList = $this->manager->getById($request->getLocale(), $request->get('shoppingListId'));
        $builder = $this->manager->update($shoppingList)
            ->addAction(ShoppingListRemoveLineItemAction::ofLineItemId($request->get('lineItemId')));

        $builder->flush();

        return $this->redirectToRoute('_ctp_example_shoppingList');
    }

    public function changeLineItemQuantityAction(Request $request)
    {
        $shoppingList = $this->manager->getById($request->getLocale(), $request->get('shoppingListId'));
        $builder = $this->manager->update($shoppingList)
            ->addAction(ShoppingListChangeLineItemQuantityAction::ofLineItemIdAndQuantity(
                $request->get('lineItemId'),
                (int)$request->get('lineItemQuantity')
            ));

        $builder->flush();

        return $this->redirectToRoute('_ctp_example_shoppingList');
    }
}
