<?php

namespace App\Controller\Admin;

use App\Entity\Achat;
use App\Entity\Stock;
use App\Entity\AchatProduit;
use App\Form\AchatType;
use App\Repository\AchatRepository;
use App\Repository\StockRepository;
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
 * @Route("/admin/achat")
 * @Security("has_role('ROLE_ADMIN')")
 */
class AdminAchatController extends AbstractController
{
    /**
     * @Route("/", name="achat")
     */
    public function index(ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $achats = $om->getRepository(Achat::class)->findAll();
        return $this->render('Admin/Achat/index.html.twig', [
          'achats' => $achats
        ]);
    }

    /**
     * @Route("/enregistrement-d-un-achat", name="add.achat")
     */
    public function save_achat(Request $request, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $achat = new Achat();
        $form = $this->createForm(AchatType::class, $achat);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $data = $request->request->all();
          $date = new \DateTime($data['date']);
          $achat->setDate($date);
          // return new Response(var_dump($data));
          $om->persist($achat);
          $om->flush();
          $this->addFlash('success', 'L\'acht du <strong>'.$achat->getDate()->format('d-m-Y').'</strong> a bien été enregistré.');
          return $this->redirectToRoute('achat.produit', ['id' => $achat->getId()]);
        }

        return $this->render('Admin/Achat/achat-add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/enregistrement-des-produits-d-un-achat-{id}", name="achat.produit")
     * @param Achat $achat
     */
    public function achat_produit(Request $request, Achat $achat, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $repoProduct = $om->getRepository(Product::class);
        $products = $repoProduct->findAll();
        if($request->isMethod('post'))
        {
          $data = $request->request->all();
          $token = $data['token'];
          if($this->isCsrfTokenValid('achat', $token)){
            // return new Response(var_dump($data));
            if(isset($data['product'])){
              $products   = $data['product'];
              $quantities = $data['quantity'];
              foreach ($products as $keyP => $valueP) {
                foreach ($quantities as $keyQ => $valueQ) {
                  if($keyP === $keyQ)
                  {
                    if(empty($quantities[$keyQ]))
                    {
                      $this->addFlash('warning', 'La quantité de produits d\'un achat ne doit pas être vide.');
                      return $this->redirectToRoute('achat.produit', ['id' => $achat->getId()]);
                    }
                    else{
                      $achatP = new AchatProduit();
                      $product = $om->getRepository(Product::class)->find($keyP);
                      $stock = $om->getRepository(Stock::class)->findOneBy(['product' => $keyP]);
                      if(empty($stock))
                      {
                        $stock = new Stock();
                        $stock->setProduct($product);
                        $stock->setQuantity($quantities[$keyQ]);
                        $om->persist($stock);
                      }
                      else {
                        $quantity = $stock->getQuantity() + $quantities[$keyQ];
                        $stock->setQuantity($quantity);
                        $stock->setUpdatedAt(new \DateTime());
                      }
                      $achatP->setProduct($product);
                      $achatP->setAchat($achat);
                      $achatP->setQuantity($quantities[$keyQ]);
                      $om->persist($achatP);
                    }
                  }
                }
              }
              $om->flush();
              $this->addFlash('success', 'Les produits de l\'acht du <strong>'.$achat->getDate()->format('d-m-Y').'</strong> ont été  avec succès.');
              return $this->redirectToRoute('achat');
            }
            else{
              $this->addFlash('warning', 'Vous n\'avez sélectionné aucun produit pour cet achat.');
              return $this->redirectToRoute('achat.produit', ['id' => $achat->getId()]);
            }
          }
        }

        return $this->render('Admin/Achat/produit-d-un-achat.html.twig', [
            'products' => $products
        ]);
    }

    /**
     * @Route("/edition-d-un-achat/{id}", name="edit.achat")
     * @param Achat $achat
     */
    public function edit_achat(Request $request, Achat $achat, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $form = $this->createForm(AchatType::class, $achat);
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

        return $this->render('Admin/Achat/achat-edit.html.twig', [
            'form'  => $form->createView(),
            'achat' => $achat,
        ]);
    }

    /**
     * @Route("/details-d-un-achat/{id}", name="details.achat")
     * @param Achat $achat
     */
    public function details_achat(Request $request, Achat $achat, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        return $this->render('Admin/Achat/achat-details.html.twig', [
            'achat' => $achat,
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

}
