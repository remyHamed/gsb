<?php

namespace App\Form;

use App\Entity\Employee;
use App\Entity\Shift;
use App\Entity\Week;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShiftType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', null, [
                'widget' => 'single_text'
            ])
            ->add('startTime')
            ->add('endTime')
            ->add('skillsRequired')
            ->add('week', EntityType::class, [
                'class' => Week::class,
                'choice_label' => 'id',
            ])
            ->add('assignedEmployees', EntityType::class, [
                'class' => Employee::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Shift::class,
        ]);
    }
}
