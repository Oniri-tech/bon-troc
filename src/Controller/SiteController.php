<?php

namespace App\Controller;

use App\Entity\Annonces;
use App\Entity\User;
use App\Repository\AnnoncesRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return $this->render('site/index.html.twig', [
        'controller_name' => 'SiteController',
        ]);
    }
    /**
     * @Route("/user/{id}/profil", name ="profile")
     */
    public function profil($id, UserRepository $repoUse, AnnoncesRepository $repoann)
    {
        $annonces = $repoann->findBy(["id_posteur" => $id]);
        $user = $repoUse->find($id);
        return $this->render('site/profil.html.twig', [
            'user' => $user,
            'annonces' => $annonces
        ]);
    }
    /**
     * @Route("/user/{id}/new", name="new")
     */
    public function addAnnonce($id, Request $request, EntityManagerInterface $manager, UserRepository $repo)
    {
        $user = $repo->find($id);
        $annonce = new Annonces;
        $annonce->setIdPosteur($user);
        $annonce->setValide(0);
        $form = $this->createFormBuilder($annonce)
            ->add('titre', TextType::class)
            ->add('contenu', TextareaType::class)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Recherche' => 'recherche',
                    'Proposition' => 'proposition'
                ]
            ])
            ->add('contrepartie', TextareaType::class)
            ->getForm();
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() ){
            $manager->persist($annonce);
            $manager->flush();

            $this->addFlash(
                'notice',
                'Votre annonce a bien été enregistrée ! Elle sera mise en ligne après vérification par un modérateur'
            );
            return $this->redirectToRoute('profile', ['id'=> $id]);
        }

        return $this->render('site/formAnnonce.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/annonce/{id}", name="annonce")
     */
    public function showAnnonce($id, AnnoncesRepository $repo)
    {
        return $this->render('site/annonce.html.twig', [
            'annonce' => $repo->find($id),
            'posteur' => $repo->find($id)->getIdPosteur()
        ]);
    }
    
    /**
     * @Route("/annonce/{id}/update", name="update")
     */
    public function updateAnnonce($id, Request $request, EntityManagerInterface $manager, AnnoncesRepository $repo)
    {
        $annonce = $repo->find($id);
        $user = $annonce->getIdPosteur()->getId();

        if (!$annonce OR ($user != $this->getUser()->getId() && !in_array("ROLE_ADMIN", $this->getUser()->getRoles()))) {
            return $this->redirectToRoute('index');
        }
        else {
            $form = $this->createFormBuilder($annonce)
                ->add('titre', TextType::class)
                ->add('contenu', TextareaType::class)
                ->add('type', ChoiceType::class, [
                    'choices' => [
                        'Recherche' => 'recherche',
                        'Proposition' => 'proposition'
                    ]
                ])
                ->add('contrepartie', TextareaType::class)
                ->getForm();
                $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid() ){
                $annonce->setValide(0);
                $manager->persist($annonce);
                $manager->flush();

                return $this->redirectToRoute('profile', ['id'=> $user]);
            }
        }
        return $this->render('site/formAnnonce.html.twig', [
            'form' => $form->createView()
        ]);
    }
    /**
     * @Route("/annonce/{id}/delete", name="delete")
     */
    public function deleteAnnonce($id, EntityManagerInterface $manager, AnnoncesRepository $repo)
    {
        $annonce = $repo->find($id);
        $user = $annonce->getIdPosteur()->getId();

        if (!$annonce OR ($user != $this->getUser()->getId() && !in_array("ROLE_ADMIN", $this->getUser()->getRoles()))) {
            return $this->redirectToRoute('index');
        }
        else {
            $manager->remove($annonce);
            $manager->flush();
            $this->addFlash(
                'notice',
                'Cette annonce à bien été supprimée !'
            );
            if ($user == $this->getUser()->getId()) {
                return $this->redirectToRoute('profile', ['id'=> $user]);
            }
            else{
                return $this->redirectToRoute('allAnnonces');
            }
        }
    }
    /**
     * @Route("/annonces", name="allAnnonces")
     */
    public function allAnnonces(UserRepository $repoUse)
    {   
        $user = $this->getUser()->getCodePost();
        $userscode = $repoUse->findBy(['code_post' => $user]);
        $annonces = [];
        foreach ($userscode as $userexte) {
            $annoncesUser = $userexte->getAnnonces();
            foreach ($annoncesUser as $annonce){
                if ($annonce->getValide() == true) {
                    array_push($annonces, $annonce);
                }
            }
        }
        return $this->render('site/liste_annonces.html.twig', [
            'annonces' => $annonces
        ]);
    }
}
