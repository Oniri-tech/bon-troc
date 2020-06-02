<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Génère la liste des départements avec l'api API Géo
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
                'placeholder' => 'Choisir un département',
                'choices' => $departements
            ])
        /*$builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();

                // this would be your entity, i.e. SportMeetup
                $data = $event->getData();
                $deptURL = "https://geo.api.gouv.fr/departements";
                $deptJson = file_get_contents($deptURL);
                $deptArray = json_decode($deptJson);
                $code = "";
                for ($i=0; $i < count($deptArray); $i++) { 
                    $departement = $deptArray[$i];
                    if ($departement->{'nom'} == $data) {
                        $code = $departement->{'code'};
                        
                    break;
                    }
                }
                var_dump($code);
                
                $commsURL = "https://geo.api.gouv.fr/departements/73/communes";
                $commsJson = file_get_contents($commsURL);
                $commsArray = json_decode($commsJson);
                $codeArr = array();
                for ($i=0; $i < count($commsArray); $i++) { 
                    $comm = $commsArray[$i];
                    $codePost = $comm->{'codesPostaux'};
                    for ($j=0; $j < count($codePost); $j++) { 
                        array_push($codeArr, $codePost[$j]);
                        $codeArr[$codePost[$j]] = $codeArr[$i*2+$j];
                        unset($codeArr[$i*2+$j]);
                    }
                    
                }

                $form->add('code_post', ChoiceType::class, [
                    'placeholder' => 'Choisir un code postal',
                    'choices' => $codeArr
                ]);
            }
        );*/
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
