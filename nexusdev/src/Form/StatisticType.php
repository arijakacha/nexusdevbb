<?php

namespace App\Form;

use App\Entity\Game;
use App\Entity\Player;
use App\Entity\Statistic;
use App\Entity\Team;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatisticType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('game', EntityType::class, [
                'class' => Game::class,
                'choice_label' => 'name',
            ])
            ->add('team', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'name',
                'required' => false,
            ])
            ->add('player', EntityType::class, [
                'class' => Player::class,
                'choice_label' => 'nickname',
                'required' => false,
            ])
            ->add('matchesPlayed')
            ->add('wins')
            ->add('losses')
            ->add('kills')
            ->add('deaths')
            ->add('assists')
            ->add('winRate')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Statistic::class,
        ]);
    }
}
