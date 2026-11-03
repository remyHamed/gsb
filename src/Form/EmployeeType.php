<?php

namespace App\Form;

use App\Entity\Account;
use App\Entity\Employee;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('dateAnciennete', null, [
                'widget' => 'single_text'
            ])
            ->add('weeklyHours')
            ->add('availableSlots')
            ->add('datesExclues')
            ->add('skills')
            ->add('isActive')
            ->add('createdAt', null, [
                'widget' => 'single_text'
            ])
            ->add('account', EntityType::class, [
                'class' => Account::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employee::class,
        ]);
    }
}
