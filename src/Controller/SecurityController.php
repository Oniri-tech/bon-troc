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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/inscription", name="inscription")
     */
    public function inscription(Request $request, EntityManagerInterface $manager, UserPasswordEncoderInterface $encoder, MailerInterface $mailer)
    {
        $user = new User();
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

            $email = (new TemplatedEmail())
            ->from('admin@lebontroc.com')
            ->to($user->getMail())
            ->subject('Validation de votre compte Le Bon Troc')
            ->htmlTemplate('mails/activation.html.twig')
            ->context([
                'token' => $user->getActivationToken()
            ]);
            $mailer->send($email);
            
            $this->addFlash(
                'notice',
                'Votre compte a été crée ! Rendez-vous sur votre boite mail pour valider votre inscription'
            );

            return $this->redirectToRoute('index');
        }

        return $this->render('security/inscription.html.twig', [
            'form'=> $form->createView(),
            'user'=> $user,
        ]);
    }
    /**
     * @Route("/user/{id}/settings", name="settings")
     */
    public function settings(User $user, Request $request, EntityManagerInterface $manager)
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
        $form = $this->createFormBuilder($user)
                    ->add('nom')
                    ->add('prenom')
                    ->add('mail')
                    ->add('telephone')
                    ->add('departement', ChoiceType::class, [
                        'placeholder' => 'Choisir un département',
                        'choices' => $departements
                    ])
                    ->add('code_post')
                    ->add('commune')
                    ->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $manager->persist($user);
                $manager->flush();
            }

        return $this->render('security/setting.html.twig',[
            'form' => $form->createView(),
            'user' => $user
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

    /**
     * @Route("/login/forgot", name="forgot_password")
     */
    public function forgot()
    {
        return $this->render('security/forgot.html.twig');
    }

    /**
     * @Route("/login/forgot/send", name="send_reset_mail")
     */
    public function reset(UserRepository $repo, EntityManagerInterface $manager, MailerInterface $mailer)
    {
        $user = $repo->findOneBy(['mail' => $_POST['email']]);

        if ($user) {
            $user->setResetToken(md5(uniqid()));

            $manager->persist($user);
            $manager->flush();

            $email = (new TemplatedEmail())
                ->from('admin@lebontroc.com')
                ->to($user->getMail())
                ->subject('Validation de votre compte Le Bon Troc')
                ->htmlTemplate('mails/reset.html.twig')
                ->context([
                    'user' => $user
                ]);
                $mailer->send($email);
                $this->addFlash(
                    'notice',
                    'Le mail à été envoyé, vérifiez votre boite mail :)'
                );
                return $this->redirectToRoute('index');
        }
        else {
            $this->addFlash(
                'notice',
                "Cette adresse mail n'est associée à aucun compte, désolé !"
            );
            return $this->redirectToRoute('forgot_password');
        }
    }

    /**
     * @Route("password/{token}/reset", name="reset_pass")
     * @Route("profile/{id}/changepass", name="change_pass")
     */
    public function resetPass($token = null, $user=null, UserRepository $repo, EntityManagerInterface $manager, Request $request, UserPasswordEncoderInterface $encoder)
    {
        if (!$user) {
            $user = $repo->findOneBy(['reset_token' => $token]);            
        }
        if ($user) {
            $form = $this->createFormBuilder($user)
                        ->add('password', PasswordType::class)
                        ->add('confirm_password', PasswordType::class)
                        ->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $hash = $encoder->encodePassword($user, $user->getPassword());
                $user->setPassword($hash);

                $manager->persist($user);
                $manager->flush();

                $this->addFlash('notice', 'Votre mot de passe à bien été modifié !');
                return $this->redirectToRoute('index');
            }
            return $this->render('security/new_password.html.twig',[
                'form' => $form->createView()
            ]);
        }
    }
}
