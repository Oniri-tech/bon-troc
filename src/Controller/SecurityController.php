<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\InscriptionType;
use App\Repository\AnnoncesRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/inscription", name="inscription")
     * @Route("/user/{id}/settings", name="settings")
     */
    public function setAccount(User $user = null, Request $request, EntityManagerInterface $manager, UserPasswordEncoderInterface $encoder)
    {
        if (!$user) {
            $user = new User();
        }
        $form = $this->createForm(InscriptionType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $user->setPassword(htmlspecialchars($user->getPassword()));
            $hash = $encoder->encodePassword($user, $user->getPassword());

            $user->setPassword($hash);

            $manager->persist($user);
            $manager->flush();

            return $this->redirectToRoute('index');
        }

        return $this->render('security/setting.html.twig', [
            'form'=> $form->createView(),
            'user'=> $user,
        ]);
    }
    /**
     * @Route("/logout", name="security_logout")
     */
    public function logout()
    {}
    /**
     * @Route("/user/{id}/delete", name="deleteUser")
     */
    public function deleteUser($id, UserRepository $repo, EntityManagerInterface $manager)
    {
        $user = $repo->find($id);
        if ($user->getId() != $this->getUser()->getId() && !in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('index');
        }
        else{
            if ($user->getId() == $this->getUser()->getId()){
                $this->logout();
            }
            $manager->remove($user);
            $manager->flush();
            $this->addFlash(
                'notice',
                'Le compte à bien été supprimé du site !'
            );
            return $this->redirectToRoute('index');
        }
        
    }
}
