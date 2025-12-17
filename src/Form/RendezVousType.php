<?php

namespace App\Form;

use App\Entity\ReserverRendezVous;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RendezVousType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('email')
            ->add('telephone')
            ->add('specialite', ChoiceType::class, [
                'choices' => [
                    'Cardiologie' => 'Cardiologie',
                    'Pédiatrie' => 'Pédiatrie',
                    'Dermatologie' => 'Dermatologie',
                    'Gynécologie' => 'Gynécologie',
                ],
                'placeholder' => 'Choisissez une spécialité'
            ])
            ->add('date_rdv', DateType::class, [
                'widget' => 'single_text'
            ])
            ->add('heure_rdv', TimeType::class, [
                'widget' => 'single_text'
            ])
            ->add('message');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReserverRendezVous::class,
        ]);
    }
}
