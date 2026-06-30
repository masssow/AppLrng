<?php

declare(strict_types=1);

namespace App\Http\Form;

use App\Domain\Shared\Entity\Domaine;
use App\Domain\Shared\Enum\ModeAccompagnement;
use App\Domain\Shared\Enum\NiveauMaitrise;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class ParcoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label'       => 'form.titre',
                'constraints' => [new NotBlank(), new Length(['max' => 255])],
            ])
            ->add('objectif', TextareaType::class, [
                'label'       => 'form.objectif',
                'constraints' => [new NotBlank()],
            ])
            ->add('domaine', EntityType::class, [
                'class'       => Domaine::class,
                'choice_label'=> 'label',
                'label'       => 'form.domaine',
                'constraints' => [new NotBlank()],
            ])
            ->add('niveau', ChoiceType::class, [
                'label'   => 'form.niveau',
                'choices' => array_combine(
                    array_map(fn($c) => 'niveau.' . strtolower($c->value), NiveauMaitrise::cases()),
                    NiveauMaitrise::cases()
                ),
            ])
            ->add('modeAccompagnement', ChoiceType::class, [
                'label'   => 'form.mode_accompagnement',
                'choices' => array_combine(
                    array_map(fn($c) => 'mode.' . strtolower($c->value), ModeAccompagnement::cases()),
                    ModeAccompagnement::cases()
                ),
            ])
            ->add('dureeCibleSemaines', IntegerType::class, [
                'label'       => 'form.duree_cible_semaines',
                'constraints' => [new NotBlank(), new Positive()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
