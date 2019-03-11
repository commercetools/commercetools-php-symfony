<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Model\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, ['required' => false]);
        $builder->add('salutation', ChoiceType::class, [
            'choices' => ['Mr' => 'Mr', 'Mrs' => 'Mrs']
        ]);
        $builder->add('firstName', TextType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('lastName', TextType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('email', EmailType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('streetName', TextType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('streetNumber', TextType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('building', TextType::class, ['required' => false]);
        $builder->add('apartment', TextType::class, ['required' => false]);
        $builder->add('department', TextType::class, ['required' => false]);
        $builder->add('city', TextType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('country', CountryType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('region', TextType::class, ['required' => false]);
        $builder->add('state', TextType::class, ['required' => false]);
        $builder->add('pOBox', TextType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('postalCode', TextType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('additionalAddressInfo', TextareaType::class, [
            'required' => false, 'attr'  => ['class' => 'form_text']
        ]);
        $builder->add('additionalStreetInfo', TextareaType::class, [
            'required' => false, 'attr'  => ['class' => 'form_text']
        ]);
        $builder->add('phone', TextType::class, ['required' => false]);
        $builder->add('mobile', TextType::class, ['required' => false]);
    }
}
