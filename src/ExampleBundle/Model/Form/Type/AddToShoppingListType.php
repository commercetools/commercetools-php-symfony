<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\ExampleBundle\Model\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AddToShoppingListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('_productId', HiddenType::class);

        if (isset($options['data']['variantIdText']) && $options['data']['variantIdText'] === true) {
            $builder->add(
                '_variantId',
                TextType::class
            );
        } else {
            $variantChoices = (isset($options['data']['variant_choices']) ? $options['data']['variant_choices'] : []);
            $builder->add(
                '_variantId',
                ChoiceType::class,
                [
                    'choices' => $variantChoices,
                    'label' => "Select the variant",
                    'attr' => [
                        'class' => 'form form-control form-group'
                    ]
                ]
            );
        }

        $builder
            ->add(
                '_shoppingListId',
                ChoiceType::class,
                [
                    'choices' => $options['data']['shopping_lists'],
                    'label' => "Select the shopping list to add to",
                    'attr' => [
                        'class' => 'form form-control form-group',
                    ],
                ]
            )
            ->add(
                '_addToShoppingList',
                SubmitType::class,
                [
                    'label' => 'Add to Shopping List',
                    'attr' => [
                        'class' => 'btn-default btn',
                    ],
                ]
            )
        ;
    }
}
