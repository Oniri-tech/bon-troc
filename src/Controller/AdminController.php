<?php

namespace App\Controller;

use App\Repository\AnnoncesRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

/**
* Require ROLE_ADMIN for *every* controller method in this class.
*
* @IsGranted("ROLE_ADMIN")
*/
class AdminController extends AbstractController
{
    /**
     * @Route("/admin/annoncesWait", name="annoncesWait")
     */
    public function waiting(AnnoncesRepository $repo)
    {
        $annonces = $repo->findBy(['valide'=>false]);
        return $this->render('admin/waiting.html.twig', [
            'annonces' => $annonces
        ]);
    }
    /**
     * @Route("/annonce/{id}/validate", name="validate")
     */
    public function validate($id, AnnoncesRepository $repo, EntityManagerInterface $manager)
    {
        $annonce = $repo->find($id);
        $annonce->setValide(true);
        $manager->persist($annonce);
        $manager->flush();

        return $this->redirectToRoute('index');
    }
    /**
     * @Route("/admin/allUser", name="allUser")
     */
    public function allUsers(UserRepository $repo)
    {
        $users = $repo->findAll();
        return $this->render('admin/all_users.html.twig', [
            'users' =>$users
        ]);
    }
}
