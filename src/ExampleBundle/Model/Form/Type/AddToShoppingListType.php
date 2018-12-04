<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Model\Form\Type;

use Commercetools\Symfony\ExampleBundle\Entity\ProductToShoppingList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class AddToShoppingListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var ProductToShoppingList $productEntity */
        $productEntity = $options['data'];

        $builder
            ->add('productId', HiddenType::class)
            ->add('variantId', HiddenType::class)
            ->add('shoppingListId', HiddenType::class);

        if (!empty($productEntity->getAllVariants())) {
            $builder->add('variantId', ChoiceType::class, [
                'choices' => $productEntity->getAllVariants(),
                'label' => "Select the variant",
                'attr' => ['class' => 'form']
            ]);
        }

        if (!empty($productEntity->getAvailableShoppingLists())) {
            $variantChoices = $productEntity->getAvailableShoppingLists();
            $builder->add('shoppingListId', ChoiceType::class, [
                'choices' => $variantChoices,
                'label' => 'Add to Shopping List',
                'attr' => ['class' => 'form']
            ]);
        }

        $builder->add('addToShoppingList', SubmitType::class, [
            'label' => 'Add to Shopping List',
            'attr' => ['class' => 'btn-default btn'],
        ]);
    }
}
