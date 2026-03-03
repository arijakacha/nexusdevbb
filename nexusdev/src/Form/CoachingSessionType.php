<?php

namespace App\Form;

use App\Entity\Coach;
use App\Entity\CoachingSession;
use App\Entity\Player;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoachingSessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('player', EntityType::class, [
                'class' => Player::class,
                'choice_label' => 'nickname',
                'placeholder' => 'Select a player',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('coach', EntityType::class, [
                'class' => Coach::class,
                'choice_label' => function (Coach $coach) {
                    return $coach->getUser()->getUsername();
                },
                'placeholder' => 'Select a coach',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('scheduledAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Scheduled Date & Time',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Select date and time'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'PENDING',
                    'Confirmed' => 'CONFIRMED',
                    'Done' => 'DONE',
                    'Cancelled' => 'CANCELLED',
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CoachingSession::class,
        ]);
    }
}
