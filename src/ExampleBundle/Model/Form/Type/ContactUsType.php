<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Model\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class ContactUsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', ChoiceType::class, ['required' => false, 'label' => 'Title', 'choices'  => [
            'Mr' => 'mr',
            'Mrs' => 'mrs',
            'Other' => 'other',
        ]]);
        $builder->add('firstName', TextType::class, ['required' => true, 'label' => 'First Name']);
        $builder->add('lastName', TextType::class, ['required' => true, 'label' => 'Last Name']);
        $builder->add('email', EmailType::class, ['required' => true, 'label' => 'Email']);
        $builder->add('subject', TextType::class, ['required' => true, 'label' => 'Subject']);
        $builder->add('phone', TextType::class, ['required' => false, 'label' => 'Phone Number']);
        $builder->add('orderNumber', TextType::class, ['required' => false, 'label' => 'Order Number']);
        $builder->add('message', TextareaType::class, ['required' => true, 'label' => 'Message']);
        $builder->add('submit', SubmitType::class);
    }
}
