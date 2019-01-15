<?php

namespace App\Controller;

use App\Entity\Achat;
use App\Entity\Stock;
use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Debiteur;
use App\Entity\Commande;
use App\Entity\Reglement;
use App\Entity\CommandeProduit;
use App\Entity\AchatProduit;
use App\Repository\AchatRepository;
use App\Repository\DebiteurRepository;
use App\Repository\CategoryRepository;
use App\Repository\ReglementRepository;
use App\Repository\CommandeProduitRepository;
use App\Repository\StockRepository;
use App\Form\ProductType;
use App\Form\DebiteurType;
use App\Form\CommandeType;
use App\Service\CheckConnectedUser;
use App\Repository\ProductRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @Route("/vente")
 */
class VenteController extends AbstractController
{
    /**
     * @Route("/", name="vente")
     */
    public function index(ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $products = $om->getRepository(Product::class)->findAll();
        $stocks = $om->getRepository(Stock::class)->findAll();
        return $this->render('Vente/index.html.twig', [
          'products' => $products,
          'stocks' => $stocks
        ]);
    }

    /**
     * @Route("/toutes-les-ventes", name="ventes")
     */
    public function ventes(ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $ventes = $om->getRepository(Commande::class)->findAll();
        return $this->render('Vente/ventes.html.twig', [
          'ventes' => $ventes,
        ]);
    }

    /**
     * @Route("/stock", name="stock.user")
     */
    public function stock(ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $categories = $om->getRepository(Category::class)->findAll();
        $products = $om->getRepository(Product::class)->findAll();
        $stock    = $om->getRepository(Stock::class)->findAll();
        return $this->render('Vente/stock.html.twig', [
          'categories' => $categories,
          'products' => $products,
          'stock' => $stock,
        ]);
    }

    /**
     * @Route("/ajouter-produit-a-la-commande-{id}", name="add.product.command")
     * @param Product $product
     */
    public function add_product_command(Request $request, ObjectManager $om, Product $product, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $productId = $product->getId();
        // Get Value from session
        $ids = $this->get('session')->get('aBasket');
        $stock = $om->getRepository(Stock::class)->findOneBy(['product' => $productId]);
        if($stock->getQuantity() == 0)
        {
          $this->addFlash('warning', '<strong>'.$product->getName().' '.$product->getType().'</strong> est fini en stock.');
          return $this->redirectToRoute('vente');
        }
        // On va vérifier la session pour voir si le produit n'est pas déjà sélectionné
        if(!empty($ids)){

          foreach ($ids as $key => $value) {
            if($value === $productId){
              $this->addFlash('warning', '<strong>'.$product->getName().' '.$product->getType().'</strong> est déjà ajouté(e).');
              return $this->redirectToRoute('vente');
            }
          }
        }
        // Append value to retrieved array.
        $ids[] = $productId;
        // Set value back to session
        $this->get('session')->set('aBasket', $ids);

        $this->addFlash('success', '<strong>'.$product->getName().' '.$product->getType().'</strong> ajouté(e) à la commande.');
        return $this->redirectToRoute('vente');
    }

    /**
     * @Route("/annuler-la-commande-en-cours", name="reset.command")
     */
    public function reset_commande(ObjectManager $om)
    {
        $this->get('session')->remove('aBasket');
        $this->addFlash('success', 'La commande a été annulée.');
        return $this->redirectToRoute('vente');
    }

    /**
     * @Route("/enregistrement-d-une-commande", name="save.command")
     */
    public function save_commande(Request $request, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $ids = $this->get('session')->get('aBasket');
        $products = $om->getRepository(Product::class)->findSelectedProducts($ids);
        $commande = new Commande();
        // $form = $this->createForm(CommandeType::class, $commande);
        // $form->handleRequest($request);
        if($request->isMethod('post'))
        {
          $data = $request->request->all();
          $token = $data['token'];
          if($this->isCsrfTokenValid('vente', $token)){
            $date = new \DateTime($data['date']);

            // Soit les variables
            //  - $qte les différentes quantités des différents produits et
            //  - $price les différents prix unitaires
            $qte = $data['quantityH'];
            $price = $data['priceH'];
            $totalGeneral = 0;
            $t = [];
            // Pour chaque produit de la commande, on doit enregistrer des informations (prix unitaire, qte ...)
            foreach ($price as $priceKey => $priceValue) {
              foreach ($qte as $key => $value) {
                if($priceKey == $key){
                  $prixTotal = 0;
                  $product = $om->getRepository(Product::class)->find($key);
                  $stock = $om->getRepository(Stock::class)->findOneBy(['product' => $key]);
                  // Quand on recupère le stock, on vérifie qu'il est possible de vendre la quantité demandée
                  if($stock->getQuantity() < $value)
                  {
                    $this->addFlash('error', 'La quantité demandée pour le produit '.$product->getName().' '.$product->getType().' n\'est pas disponible en stock.');
                    return $this->redirectToRoute('vente');
                  }
                  // On enregistre d'abord les détails de commande
                  $prixTotal = $value * $priceValue;
                  $commandeProduit = new CommandeProduit();
                  $commandeProduit->setCommande($commande);
                  $commandeProduit->setProduct($product);
                  $commandeProduit->setQuantity($value);
                  $commandeProduit->setPrixUnitaire($priceValue);
                  $commandeProduit->setPrixTotal($prixTotal);
                  $totalGeneral += $prixTotal;
                  $om->persist($commandeProduit);
                  // Ensuite, on met à jour le stock
                  $stockQte = $stock->getQuantity() - $value;
                  $stock->setQuantity($stockQte);
                  $stock->setUpdatedAt(new \DateTime());
                  $t[] = ['prod' => $key, 'qte' => $value, 'price' => $priceValue, 'total' => $prixTotal];
                }
              }
            }
            // return new Response(var_dump($t));
            $commande->setDate($date);
            $commande->setSolde(FALSE);
            $commande->setPrixTotal($totalGeneral);
            $om->persist($commande);
            $om->flush();
            $this->get('session')->remove('aBasket');
            $this->addFlash('success', 'La vente a bien été enregistrée.');
            return $this->redirectToRoute('reglement', ['id' => $commande->getId()]);
          }
        }

        return $this->render('Vente/vente-add.html.twig', [
            // 'form' => $form->createView(),
            'products' => $products
        ]);
    }

    /**
     * @Route("/reglement-commande/{id}", name="reglement")
     * @param Commande $commande
     */
    public function reglement(Request $request, Commande $commande, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        if($request->isMethod('post'))
        {
          $data = $request->request->all();
          $montant = $data['montant'];
          $date = new \DateTime($data['date']);
          $token = $data['token'];
          if($this->isCsrfTokenValid('reglement', $token)){
            if($montant <= 0){
              $this->addFlash('error', 'Le montant du règlement ne doit pas être inférieur ou égal à 0.');
              return $this->redirectToRoute('reglement', ['id' => $commande->getId()]);
            }
            elseif($montant > $commande->getPrixTotal()){
              $this->addFlash('error', 'Le montant saisi est supérieur au montant total de la commande.');
              return $this->redirectToRoute('reglement', ['id' => $commande->getId()]);
            }
            elseif($montant < $commande->getPrixTotal()) {
              $reglement = new Reglement();
              $reglement->setCommande($commande);
              $reglement->setDate($date);
              $reglement->setMontant($montant);
              $om->persist($reglement);
              $om->flush();
              $this->addFlash('success', 'Le règlement a bien été enregistré. Mais la commande n\'est pas soldée');
              return $this->redirectToRoute('add.debiteur', ['id' => $commande->getId()]);
            }
            else{
              $reglement = new Reglement();
              $reglement->setCommande($commande);
              $reglement->setMontant($montant);
              $reglement->setDate($date);
              $commande->setSolde(TRUE);
              $commande->setUpdatedAt(new \DateTime());
              $om->persist($reglement);
              $om->flush();
              $this->addFlash('success', 'La commande a été soldée.');
              return $this->redirectToRoute('ventes');
            }
          }
        }
        return $this->render('Vente/reglement.html.twig', [
            'vente' => $commande,
        ]);
    }

    /**
     * @Route("/autre-reglement-commande/{id}", name="autre.reglement")
     * @param Commande $commande
     */
    public function autre_reglement(Request $request, Commande $commande, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        // Cette partie sert à corriger une erreur; losrque le user enregistre une commande et que pour une raison
        // ou pour une autre, il quitte la page de reglement sans avoir en enregistrer un
        $reglements = $om->getRepository(Reglement::class)->findBy(['commande' => $commande->getId()]);
        $totalReglement = 0;
        foreach ($reglements as $key => $value) {
          $totalReglement += $value->getMontant();
        }
        if($totalReglement < $commande->getPrixTotal() && $commande->getSolde() == FALSE){
          $this->addFlash('warning', 'Aucun règlement n\'a été enregistré pour le moment pour cette commande.');
          return $this->redirectToRoute('reglement', ['id' => $commande->getId()]);
        }
        // Fin de la partie

        $reglements = $om->getRepository(Reglement::class)->findBy(['commande' => $commande->getId()]);
        if($request->isMethod('post'))
        {
          $data = $request->request->all();
          $montant = $data['montant'];
          $date = new \DateTime($data['date']);
          $token = $data['token'];
          if($this->isCsrfTokenValid('reglement', $token)){
            // On va faire la somme des reglements pour savoir combien il reste à payer
            $montantReglement = 0;
            foreach ($reglements as $key => $value) {
              $montantReglement += $value->getMontant();
            }
            $reste = $commande->getPrixTotal() - $montantReglement;
            if($montant <= 0){
              $this->addFlash('error', 'Le montant du règlement ne doit pas être inférieur ou égal à 0.');
              return $this->redirectToRoute('autre.reglement', ['id' => $commande->getId()]);
            }
            elseif($montant > $reste){
              $this->addFlash('error', 'Le montant saisi n\'est pas correct. La somme des règlements ne doit pas être supérieure au montant total de la commande.');
              return $this->redirectToRoute('autre.reglement', ['id' => $commande->getId()]);
            }
            elseif($montant < $reste) {
              $reglement = new Reglement();
              $reglement->setCommande($commande);
              $reglement->setDate($date);
              $reglement->setMontant($montant);
              $om->persist($reglement);
              $om->flush();
              $this->addFlash('success', 'Le règlement a bien été enregistré. Mais la commande n\'est pas soldée');
              return $this->redirectToRoute('details.vente', ['id' => $commande->getId()]);
            }
            else{
              $reglement = new Reglement();
              $reglement->setCommande($commande);
              $reglement->setDate($date);
              $reglement->setMontant($montant);
              $commande->setSolde(TRUE);
              $commande->setUpdatedAt(new \DateTime());
              $om->persist($reglement);
              $om->flush();
              $this->addFlash('success', 'La commande a est désormais soldée.');
              return $this->redirectToRoute('ventes');
            }
          }
        }
        return $this->render('Vente/autre-reglement.html.twig', [
            'vente' => $commande,
            'reglements' => $reglements,
        ]);
    }

    /**
     * @Route("/enregistrement-debiteur/{id}", name="add.debiteur")
     * @param Commande $commande
     */
    public function add_debiteur(Request $request, Commande $commande, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $debiteur = new Debiteur();
        $form = $this->createForm(DebiteurType::class, $debiteur);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $commande->setDebiteur($debiteur);
          $commande->setUpdatedAt(new \DateTime());
          $om->persist($debiteur);
          $om->flush();
          $this->addFlash('success', 'Informations sur le client enregistrées avec succès.');
          return $this->redirectToRoute('ventes');
        }
        return $this->render('Vente/debiteur-add.html.twig', [
            'vente' => $commande,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/details-d-une-vente/{id}", name="details.vente")
     * @param Commande $commande
     */
    public function details_vente(Request $request, Commande $commande, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        // Cette partie sert à corriger une erreur; losrque le user enregistre une commande et que pour une raison
        // ou pour une autre, il quitte la page de reglement sans avoir en enregistrer un
        $reglements = $om->getRepository(Reglement::class)->findBy(['commande' => $commande->getId()]);
        $totalReglement = 0;
        foreach ($reglements as $key => $value) {
          $totalReglement += $value->getMontant();
        }
        if($totalReglement < $commande->getPrixTotal() && $commande->getSolde() == FALSE){
          $this->addFlash('warning', 'Aucun règlement n\'a été enregistré pour le moment pour cette commande.');
          return $this->redirectToRoute('reglement', ['id' => $commande->getId()]);
        }
        // Fin de la partie
        return $this->render('Vente/vente-details.html.twig', [
            'vente'      => $commande,
            'reglements' => $reglements,
        ]);
    }

    /**
     * @Route("/enregistrement-d-une-vente", name="add.vente")
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
          $this->addFlash('success', 'L\'acht du <strong>'.$achat->getDate()->format('d-m-Y').'</strong> a bien été enregistrée.');
          return $this->redirectToRoute('achat.produit', ['id' => $achat->getId()]);
        }

        return $this->render('Admin/Achat/achat-add.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
