<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Form\ContactSellerFormType;
use App\Form\ProductType;
use App\Form\SearchProductFormType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Service\Upload;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Knp\Component\Pager\PaginatorInterface;


class ProductController extends AbstractController
{
    /**
     * @Route("/", name="product_index")
     */
    public function index(ProductRepository $productRepository, PaginatorInterface $paginator, CategoryRepository $categoryRepository, Request $request): Response
    {
        $product = $productRepository->findAllTProductVerified();

        $searchForm = $this->createForm(SearchProductFormType::class);
        $searchForm->handleRequest($request);

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $keyword = $searchForm->get('search')->getData();
            $product = $productRepository->findLike($keyword);

            if (empty($searchForm->get('search')->getData()) || $searchForm->get('search')->getData() == '' || $searchForm->get('search')->getData() == null)
            {
                $productRepository->findAllTProductVerified();
                //$keyword = '';

            }
            return $this->redirectToRoute('product_with_search', array(
                'keyword' => $keyword,
            ));
        }

        $pagination = $paginator->paginate(
            $product, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            10 /*limit per page*/
        );

        // request for product homepage
        $BestSell = $productRepository->findAllTProductVerifiedAndBestSell() ;
        $newProduct = $productRepository->findAllTProductVerifiedAndNewProduct() ;
        $FreeProduct = $productRepository->findAllTProductVerifiedAndFreeProduct() ;

        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAllTProductVerified(),
            'categories' => $categoryRepository->findBy(['active' => 1]),
            'pagination' => $pagination,
            'searchForm' => $searchForm->createView(),
            'bestSell' => $BestSell,
            'newProduct' => $newProduct,
            'FreeProduct' => $FreeProduct,
        ]);
    }

    /**
     * @Route("/search/{keyword}", name="product_with_search")
     */
    public function productWithSearch($keyword, ProductRepository $productRepository, PaginatorInterface $paginator, Request $request, CategoryRepository $categoryRepository): Response
    {
        $searchForm = $this->createForm(SearchProductFormType::class);
        $searchForm->handleRequest($request);

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $keyword = $searchForm->get('search')->getData();
            
            $this->redirectToRoute('product_with_search', array(
                'keyword' => $keyword,
                ));
        }
        
        if(!empty($keyword)) {
            $products = $productRepository->findLike($keyword);
        }
        else{
            $products = $productRepository->findAllTProductVerified();
        }

        $pagination = $paginator->paginate(
            $products, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            10 /*limit per page*/
        );

        return $this->render('product/searchProduct.html.twig', [
            'pagination' => $pagination,
            'searchForm' => $searchForm->createView(),
            'categories' => $categoryRepository->findBy(['active' => 1]),
        ]);
    }


    /**
     * @Route("/category/{id}", name="product_with_category", methods={"GET"})
     */
    public function productWithCategory(ProductRepository $productRepository, PaginatorInterface $paginator, Request $request, CategoryRepository $categoryRepository, $id): Response
    {
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $products = $productRepository->findBy(['category' => $id]);

        $searchForm = $this->createForm(SearchProductFormType::class);
        $searchForm->handleRequest($request);

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $keyword = $searchForm->get('search')->getData();
            $products = $productRepository->findLike($keyword);

            if (empty($searchForm->get('search')->getData()) || $searchForm->get('search')->getData() == '')
            {
                $productRepository->findAllTProductVerified();
            }
        }

        $pagination = $paginator->paginate(
            $products, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            10 /*limit per page*/
        );

        $categoryCurrent = $categoryRepository->find($id);

        return $this->render('product/categoryProduct.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findBy(['active' => 1]),
            'categoryCurrent' => $categoryCurrent,
            'pagination' => $pagination,
            'searchForm' => $searchForm->createView()
        ]);
    }

    /**
     * @Route("product/new", name="product_new", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_AUTHOR')")
     * 
     */
    public function new(Request $request, Upload $upload, \Swift_Mailer $mailer, CategoryRepository $categoryRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'User tried to access a page without having ROLE_ADMIN');

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $product->setUser($this->getUser());


            $fileName1 = $upload->upload($form->get('img1')->getData());
            $product->setImg1($fileName1);

            if($form->get('img2')->getData()){
                $fileName2 = $upload->upload($form->get('img2')->getData());
                $product->setImg2($fileName2);
            }
            if($form->get('img3')->getData()){
                $fileName3 = $upload->upload($form->get('img3')->getData());
                $product->setImg3($fileName3);
            }

            if($form->get('file')->getData()){
                $fileName3 = $upload->uploadFile($form->get('file')->getData());
                $product->setFile($fileName3);
            }
            //upload des images
            
            $product->setPublished(new \Datetime('now'));   //2020-06-06 14:52:49
            $entityManager->persist($product);
            $entityManager->flush();

             // \Swift_Mailer $mailer
             $message = (new \Swift_Message('Web-Item-Market'))
             ->setFrom('sacha6623@gmail.com')
             ->setTo($this->getUser()->getEmail())
             ->setBody(
                 $this->renderView(
                     'Emails/upload.html.twig',
                     []),
              'text/html');
            $mailer->send($message);

         $this->addFlash('success', "Email has been send");
            
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'categories' => $categoryRepository->findBy(['active' => 1]),
        ]);
    }

    /**
     * @Route("product/{id}", name="product_show", methods={"GET"})
     */
    public function show(Product $product,CategoryRepository $categoryRepository,  $id): Response
    {
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $products = $productRepository->findBy(['id' => $id]);

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'categories' => $categoryRepository->findBy(['active' => 1]),
        ]);
    }

    /**
     * @Route("product/{id}/contact/{idUser}", name="contact_seller")
     */
    public function contact_seller(\Swift_Mailer $mailer, Request $request, Product $product,CategoryRepository $categoryRepository, UserRepository $userRepository, $id, $idUser): Response
    {
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $product = $productRepository->findOneBy(['id' => $id]);

        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $EmailSeller = $userRepository->findOneBy(['id' => $idUser])->getEmail();
        // chercher mail de user a l'aide de l'id

        $form = $this->createForm(ContactSellerFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // Mail $form->get('img2')->getData()
            $message = (new \Swift_Message('Web-Item-Market'))
             ->setFrom('sacha6623@gmail.com')
             ->setTo($this->getUser()->getEmail())
             ->setBody(
                 $this->renderView(
                     'Emails/contactSeller.html.twig',[
                         'product' => $product,
                         'client' => $form->get('Email')->getData(),
                         'Subject' => $form->get('Subject')->getData(),
                         'Message' => $form->get('Message')->getData()
                     ]),
              'text/html');
            $mailer->send($message);

         $this->addFlash('success', "Email has been send");
        }
        

        return $this->render('product/contactSeller.html.twig', [
            'product' => $product,
            'categories' => $categoryRepository->findBy(['active' => 1]),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("product/{id}/edit", name="product_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_AUTHOR')")
     */
    public function editProduct(Request $request, Product $product, Upload $upload, EntityManagerInterface $entityManager, $id): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        $img1 = $product->getImg1();

        //dd($product->getImg1());
            //dd($img1);
        if ($form->isSubmitted() && $form->isValid()) {
            /*
            dd($product->getImg1());
            if($product->getImg1() != null){unlink('product/img/' . $img1);};
            if($product->getImg2() != null){unlink('product/img/' . $product->getImg2());};
            if($product->getImg3() != null){unlink('product/img/' . $product->getImg3());};
            if($product->getFile() != null){unlink('product/' . $product->getFile());};
            */

            if($form->get('img1')->getData()){
                $fileName1 = $upload->upload($form->get('img1')->getData());
                $product->setImg1($fileName1);
            }
            if($form->get('img2')->getData()){
                $fileName2 = $upload->upload($form->get('img2')->getData());
                $product->setImg2($fileName2);
            }
            if($form->get('img3')->getData()){
                $fileName3 = $upload->upload($form->get('img3')->getData());
                $product->setImg3($fileName3);
            }

            if($form->get('file')->getData()){
                $fileName3 = $upload->uploadFile($form->get('file')->getData());
                $product->setFile($fileName3);
            }
            
            $product->setPublished(new \Datetime('now'));   //2020-06-06 14:52:49
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('author_product');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("product/{id}", name="product_delete", methods={"DELETE"})
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_AUTHOR')")
     */
    public function delete(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('product_index');
    }
}
