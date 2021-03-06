<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Ticket;
use App\Entity\User;
use App\Form\PayoutFormType;
use App\Form\ProductType;
use App\Form\RejectProductFormType;
use App\Form\ResolveTicketType;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Service\MakeJsonFormat;
use App\Service\payment;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Require ROLE_USER for *every* controller method in this class.
 * @IsGranted("ROLE_USER")
 * @Route("/user")
 */
class UserDashboardController extends AbstractController
{

    /**
     * @Route("/MyOrder", name="my_order")
     */
    public function myOrder(CategoryRepository $categoryRepository)
    {
        $user = $this->getUser() ;
        
        $orderRepository = $this->getDoctrine()->getRepository(Order::class);

        return $this->render('user/myOrder.html.twig', [
            'orders' => $orderRepository->findBy(['user' => $user]),
            'user' => $this->getUser(),
            'categories' => $categoryRepository->findBy(['active' => 1]),
        ]);
    }
}
