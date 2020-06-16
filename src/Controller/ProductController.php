<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\Upload;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


class ProductController extends AbstractController
{
    /**
     * @Route("/", name="product_index", methods={"GET"})
     */
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->render('product/list.html.twig', [
            'products' => $productRepository->findAllTProductVerified(),
            'categories' => $categoryRepository->findAll()
        ]);
    }

    /**
     * @Route("/category/{id}", name="product_with_category", methods={"GET"})
     */
    public function productWithCategory(ProductRepository $productRepository, CategoryRepository $categoryRepository, $id): Response
    {
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        //dd($name);
        $products = $productRepository->findBy(['category' => $id]);

        return $this->render('product/list.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll()
        ]);
    }

    /**
     * @Route("product/new", name="product_new", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_AUTHOR')")
     * 
     */
    public function new(Request $request, Upload $upload): Response
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
            //
            //upload des images
            
            $product->setPublished(new \Datetime('now'));   //2020-06-06 14:52:49
            $entityManager->persist($product);
            $entityManager->flush();
            
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("product/{id}", name="product_show", methods={"GET"})
     */
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * @Route("product/{id}/edit", name="product_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_AUTHOR')")
     */
    public function edit(Request $request, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('product_index');
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
