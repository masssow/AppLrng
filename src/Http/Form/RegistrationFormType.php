<?php

declare(strict_types=1);

namespace App\Http\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'label'       => 'form.prenom',
                'constraints' => [new NotBlank(), new Length(['min' => 2, 'max' => 100])],
            ])
            ->add('nom', TextType::class, [
                'label'       => 'form.nom',
                'constraints' => [new NotBlank(), new Length(['min' => 2, 'max' => 100])],
            ])
            ->add('email', EmailType::class, [
                'label'       => 'form.email',
                'constraints' => [new NotBlank(), new Email()],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type'           => PasswordType::class,
                'first_options'  => ['label' => 'form.password'],
                'second_options' => ['label' => 'form.password_confirm'],
                'constraints'    => [new NotBlank(), new Length(['min' => 8])],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
