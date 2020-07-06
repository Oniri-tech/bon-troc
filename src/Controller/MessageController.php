<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Form\CreateMessageType;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
/**
 * @IsGranted("ROLE_USER")
 */
class MessageController extends AbstractController
{
    /**
     * @Route("/{id}/new_message", name="create_message")
     */
    public function createMessage(User $user, Request $request, EntityManagerInterface $manager, MailerInterface $mailer)
    {
        $message = new Message();
        $message->setEmetteur($this->getUser());
        $message->setDestinataire($user);
        $message->setDate(new \DateTime());
        $form = $this->createForm(CreateMessageType::class, $message);
        

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($message);
            $manager->flush();

            $email = (new TemplatedEmail())
            ->from('admin@lebontroc.com')
            ->to($user->getMail())
            ->subject($this->getUser()->getPrenom() .' vous a envoyé un message !')
            ->htmlTemplate('mails/nouveau_message.html.twig')
            ->context([
                'message' => $message->getContenu(),
                'emetteur' => $this->getUser()
            ]);
            $mailer->send($email);

            return $this->redirectToRoute('index');
        }
        return $this->render('message/new.html.twig', [
            'destinataire' => $user,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/user/mailbox", name="mailbox")
     */
    public function inbox(MessageRepository $repo)
    {
        $messages = $repo->findBy(['destinataire' => $this->getUser()->getId()]);

        return $this->render('message/mailbox.html.twig', [
            'messages' => $messages
        ]);
    }

    /**
     * @Route("/message/{id}", name="message")
     */
    public function displayMessage(Message $message)
    {
        return $this->render('message/display_message.html.twig', [
            'message' => $message
        ]);
    }
    /**
     * @Route("/message/{id}/delete", name="delete_message")
     */
    public function deleteMessage(Message $message, EntityManagerInterface $manager)
    {
        if ($this->getUser()->getId() != $message->getDestinataire()->getId()) {
            return $this->redirectToRoute('index');
        }
        $manager->remove($message);
        $manager->flush();
        $this->addFlash(
            'notice',
            'Message supprimé avec succès !'
        );
        return $this->redirectToRoute('mailbox');
    }
}
