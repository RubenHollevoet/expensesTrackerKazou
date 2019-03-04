<?php

namespace AppBundle\Form;

use AppBundle\Entity\Region;
use AppBundle\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRegistrationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Voornaam',
                'required' => true
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Achternaam',
                'required' => true
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email'
            ])
            ->add('region', EntityType::class, [
                'class' => Region::class,
                'choice_name' => 'name',
                'label' => 'Regio'
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'invalid_message' => 'De wachtwoorden komen niet overeen',
                'first_options'  => ['label' => 'Wachtwoord'],
                'second_options' => ['label' => 'Herhaling wachtwoord'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['Default', 'Registration']
        ]);
    }
}
