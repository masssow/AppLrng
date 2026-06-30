<?php

declare(strict_types=1);

namespace App\Http\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class TraceApprentissageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('compris', TextareaType::class, [
                'label'    => 'form.compris',
                'required' => false,
            ])
            ->add('pointsFlous', TextareaType::class, [
                'label'    => 'form.points_flous',
                'required' => false,
            ])
            ->add('applicationPossible', TextareaType::class, [
                'label'    => 'form.application_possible',
                'required' => false,
            ])
            ->add('confiance', IntegerType::class, [
                'label'       => 'form.confiance',
                'required'    => false,
                'constraints' => [new Range(['min' => 1, 'max' => 5])],
            ])
            ->add('pomodorosEffectues', IntegerType::class, [
                'label'    => 'form.pomodoros_effectues',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
