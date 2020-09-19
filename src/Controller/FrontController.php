<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\ContactFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    /**
     * @Route("/review", name="review_client")
     */
    public function reviewClient()
    {
        return $this->render('front/ReviewClient.html.twig', [
        ]);
    }

    /**
     * @Route("/contact", name="contact")
     */
    public function ContacteMe(Request $request, \Swift_Mailer $mailer, EntityManagerInterface $em)
    {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$form->get('img1')->getData());

            $ticket = new Ticket;
            $ticket->setName($form->get('Name')->getData());
            $ticket->setSubject($form->get('Subject')->getData());
            $ticket->setEmail($form->get('Email')->getData());
            $ticket->setMessage($form->get('Message')->getData());
            $ticket->setStatus(0);
            $em->persist($ticket);
            $em->flush();

            
            // \Swift_Mailer $mailer
            $message = (new \Swift_Message('Web-Item-Market'))
                ->setFrom($form->get('Email')->getData())
                ->setTo('sacha6623@gmail.com')
                ->setBody(
                    $this->renderView(
                        'Emails/contact.html.twig',
                        [
                            'name' => $form->get('Name')->getData(),
                            'message' => $form->get('Message')->getData(),
                            'subject' => $form->get('Subject')->getData(),
                            'mail' => $form->get('Email')->getData(),
                        ]), 'text/html');
            $mailer->send($message);

            $this->addFlash('success', "Email has been send");
            
            $this->redirectToRoute('contact');
        }
        return $this->render('front/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/legal", name="app_legal")
     */
    public function legal()
    {
        return $this->render('front/legal.html.twig', [
        ]);
    }

    /**
     * @Route("/faq", name="app_faq")
     */
    public function faq()
    {
        return $this->render('front/faq.html.twig', [
        ]);
    }


    /**
     * @Route("/helpAuthor", name="help_author")
     */
    public function help_author()
    {
        return $this->render('front/helpAuthor.html.twig', [
        ]);
    }




}
