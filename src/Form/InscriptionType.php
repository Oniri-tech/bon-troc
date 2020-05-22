<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $deptURL = "https://geo.api.gouv.fr/departements";
        $deptJson = file_get_contents($deptURL);
        $deptArray = json_decode($deptJson);
        $departements = array();
        for ($i=0; $i < count($deptArray); $i++) { 
            $departement = $deptArray[$i];
            array_push($departements, $departement->{'nom'});
            $departements[$departement->{'nom'}] = $departements[$i];
            unset($departements[$i]);
        }

        $builder
            ->add('mail')
            ->add('nom')
            ->add('prenom')
            ->add('telephone')
            ->add('departement', ChoiceType::class, [
                'choices' => $departements
            ])
            ->add('code_post')
            ->add('commune')
            ->add('password', PasswordType::class)
            ->add('confirm_password', PasswordType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
