<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Model\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class AddReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'text',
                TextareaType::class,
                [
                'attr' => [
                    'placeholder' => 'Write a review...',
                ],
                'label' => false,
                'required' => false,
                ]
            )
            ->add('rating', ChoiceType::class, [
                'label' => 'rating',
                'choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5
                ]
            ])
            ->add('save', SubmitType::class, ['label' => 'Submit']);
    }
}
