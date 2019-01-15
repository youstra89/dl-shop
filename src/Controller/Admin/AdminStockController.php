<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Entity\Stock;
use App\Entity\Product;
use App\Form\ProductType;
use App\Service\CheckConnectedUser;
use App\Repository\ProductRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/stock")
 * @Security("has_role('ROLE_ADMIN')")
 */
class AdminStockController extends AbstractController
{
    /**
     * @Route("/", name="stock")
     */
    public function index(ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $categories = $om->getRepository(Category::class)->findAll();
        $products = $om->getRepository(Product::class)->findAll();
        $stock    = $om->getRepository(Stock::class)->findAll();
        return $this->render('Admin/Stock/index.html.twig', [
          'categories' => $categories,
          'products' => $products,
          'stock' => $stock,
        ]);
    }

    /**
     * @Route("/category", name="category")
     */
    public function category(ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $categories = $om->getRepository(Category::class)->findAll();
        return $this->render('Admin/Stock/category.html.twig', [
          'categories' => $categories
        ]);
    }

    /**
     * @Route("/ajout-de-category", name="add.category")
     */
    public function add_category(Request $request, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $om->persist($category);
          $om->flush();
          $this->addFlash('success', 'La catégorie <strong>'.$category->getName().'</strong> a bien été enregistrée.');
          return $this->redirectToRoute('category');
        }

        return $this->render('Admin/Stock/category-add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edition-de-category/{id}", name="edit.category")
     * @param Category $category
     */
    public function edit_category(Request $request, Category $category, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          // $category_db = $om->refresh($category);
          $om->flush();
          $this->addFlash('success', 'La catégorie <strong>'.$category->getName().'</strong> a été mise à jour.');
          return $this->redirectToRoute('category');
          // if($category_db != $category)
          // {
          // }
        }

        return $this->render('Admin/Stock/category-edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }


    /**
     * @Route("/produit", name="product")
     */
    public function product(ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $products = $om->getRepository(Product::class)->findAll();
        return $this->render('Admin/Stock/product.html.twig', [
          'products' => $products
        ]);
    }

    /**
     * @Route("/ajout-de-produit", name="add.product")
     */
    public function add_product(Request $request, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $name = $product->getCategory()->getName();
          $product->setName($name);
          $om->persist($product);
          $om->flush();
          $this->addFlash('success', 'Le produit <strong>'.$product->getName().' '.$product->getType().'</strong> a bien été enregistrée.');
          return $this->redirectToRoute('product');
        }

        return $this->render('Admin/Stock/product-add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edition-de-produit/{id}", name="edit.product")
     * @param Product $product
     */
    public function edit_product(Request $request, Product $product, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          // $category_db = $om->refresh($category);
          $name = $product->getCategory()->getName();
          $product->setName($name);
          $om->flush();
          $this->addFlash('success', 'Le produit <strong>'.$product->getName().' '.$product->getType().'</strong> a été mise à jour.');
          return $this->redirectToRoute('product');
          // if($category_db != $category)
          // {
          // }
        }

        return $this->render('Admin/Stock/product-edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }
}
