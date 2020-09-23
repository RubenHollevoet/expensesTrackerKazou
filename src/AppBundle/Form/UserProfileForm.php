<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 20/05/18
 * Time: 11:04
 */

namespace AppBundle\Form;

use AppBundle\Entity\Region;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class UserProfileForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Voornaam'
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Achternaam'
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email'
            ])
            ->add('iban', TextType::class, [
                'label' => 'IBAN'
            ])
            ->add('personId', TextType::class, [
                'label' => 'Rijksregisternummer'
            ])
            ->add('address', TextType::class, [
                'label' => 'Woonplaats (volledige adres)'
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
        ;
    }
}
