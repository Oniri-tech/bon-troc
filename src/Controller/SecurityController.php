<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\InscriptionType;
use App\Repository\AnnoncesRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/inscription", name="inscription")
     * @Route("/user/{id}/settings", name="settings")
     */
    public function setAccount(User $user = null, Request $request, EntityManagerInterface $manager, UserPasswordEncoderInterface $encoder, MailerInterface $mailer)
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
            if ($request->attributes->get('_route') == 'inscription' ) {
                $user->setActivationToken(md5(uniqid()));
            }
            

            $manager->persist($user);
            $manager->flush();

            if ($request->attributes->get('_route') == 'inscription' ) {
                $email = (new TemplatedEmail())
                ->from('admin@lebontroc.com')
                ->to($user->getMail())
                ->subject('Validation de votre compte Le Bon Troc')
                ->htmlTemplate('mails/activation.html.twig')
                ->context([
                    'token' => $user->getActivationToken()
                ]);
                $mailer->send($email);
            }

            

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

    /**
     * @Route("/activation/{token}", name="activation")
     */
    public function activation($token, UserRepository $repo, EntityManagerInterface $manager)
    {
        $user = $repo->findOneBy(['activation_token' => $token]);

        if (!$user) {
            throw $this->createNotFoundException('Cet Utilisateur n\'existe pas, ou a déjà été validé !');
        }
        $user->setActivationToken(null);
        $manager->persist($user);
        $manager->flush();

        $this->addFlash('message', 'Utilisateur activé avec succès');

        return $this->redirectToRoute('index');
    }
}
