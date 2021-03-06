<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\ContactFormType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    /**
     * @Route("/legal", name="app_legal")
     */
    public function legal(CategoryRepository $categoryRepository)
    {
        // Todo : no test succes 
        return $this->render('front/legal.html.twig', [
            'categories' => $categoryRepository->findBy(['active' => 1]),
        ]);
    }

    /**
     * @Route("/faq", name="app_faq")
     */
    public function faq(CategoryRepository $categoryRepository)
    {
        // Todo : no test succes 
        return $this->render('front/faq.html.twig', [
            'categories' => $categoryRepository->findBy(['active' => 1]),
        ]);
    }

    /**
     * @Route("/review", name="review_client")
     */
    public function reviewClient(CategoryRepository $categoryRepository)
    {
        // Todo : Fix bug: error 500 in prod with category repository
        // Todo : no test succes 
        return $this->render('front/ReviewClient.html.twig', [
            'categories' => $categoryRepository->findBy(['active' => 1]),
        ]);
    }

    /**
     * @Route("/helpAuthor", name="help_author")
     */
    public function help_author(CategoryRepository $categoryRepository)
    { 
        // Todo : Fix bug: error 500 in prod with category repository
        // Todo : no test succes 
        return $this->render('front/helpAuthor.html.twig', [
            'categories' => $categoryRepository->findBy(['active' => 1]),
        ]);
    }

    /**
     * @Route("/contact", name="contact")
     */
    public function ContacteMe(Request $request, \Swift_Mailer $mailer, EntityManagerInterface $em, CategoryRepository $categoryRepository)
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

            $this->addFlash('success', "Le message a été envoyé");
            
            $this->redirectToRoute('contact');
        }
        return $this->render('front/contact.html.twig', [
            'form' => $form->createView(),
            'categories' => $categoryRepository->findBy(['active' => 1]),
        ]);
    }

}
