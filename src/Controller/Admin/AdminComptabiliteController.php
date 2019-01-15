<?php

namespace App\Controller\Admin;

use App\Entity\Achat;
use App\Entity\Stock;
use App\Entity\Commande;
use App\Entity\Reglement;
use App\Entity\AchatProduit;
use App\Form\AchatType;
use App\Repository\AchatRepository;
use App\Repository\StockRepository;
use App\Repository\CommandeRepository;
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
 * @Route("/admin/comptabilite")
 * @Security("has_role('ROLE_ADMIN')")
 */
class AdminComptabiliteController extends AbstractController
{
    /**
     * @Route("/", name="comptabilite")
     */
    public function index(ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        return $this->render('Admin/Comptabilite/index.html.twig');
    }

    /**
     * @Route("/comptabilite-des-ventes-du-jour/{date}", name="vente.du.jour")
     */
    public function vente_du_jour(Request $request, ObjectManager $om, $date, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $date = new \DateTime($date);
        // $date = $date->format('Y-m-d');
        $ventes = $om->getRepository(Commande::class)->findBy(['date' => $date]);
        if(empty($ventes)){
          $this->addFlash('error', 'La date saisie n\'est pas correcte.');
          return $this->redirectToRoute('comptabilite.journaliere');
        }
        return $this->render('Admin/Comptabilite/vente-du-jour.html.twig', [
          'ventes' => $ventes
        ]);
    }

    /**
     * @Route("/comptabilite-journaliere", name="comptabilite.journaliere")
     */
    public function comptabilite_journaliere(Request $request, ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $mois = $request->get('mois');
        if(empty($mois))
          $mois = (new \DateTime())->format('Y-m');
        $ventes = $om->getRepository(Commande::class)->venteDuMois($mois);
        if(empty($ventes)){
          $this->addFlash('error', 'La date saisie n\'est pas correcte.');
          return $this->redirectToRoute('comptabilite.mensuelle');
        }
        return $this->render('Admin/Comptabilite/comptabilite-journaliere.html.twig', [
          'ventes' => $ventes
        ]);
    }

    /**
     * @Route("/comptabilite-mensuelle", name="comptabilite.mensuelle")
     */
    public function comptabilite_mensuelle(ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $repoCommande = $om->getRepository(Commande::class);
        $mois  = $repoCommande->lesDifferentesDates();
        $gains = [];
        foreach ($mois as $key => $value) {
          $ventes = $repoCommande->venteDuMois($value['1']);
          $somme = 0;
          foreach ($ventes as $keyV => $valueV) {
            $somme += $valueV['1'];
          }
          $gains[$value['1']] = $somme;
        }
        return $this->render('Admin/Comptabilite/comptabilite-mensuelle.html.twig', [
          'mois'  => $mois,
          'gains' => $gains,
        ]);
    }

    /**
     * @Route("/comptabilite-les-débits", name="comptabilite.debit")
     */
    public function debits(ObjectManager $om, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $repoCommande = $om->getRepository(Commande::class);
        $repoReglement = $om->getRepository(Reglement::class);
        $commandes  = $repoCommande->findBy(['solde' => false]);
        $reglements  = $repoReglement->reglementsIncomplets();
        dump($reglements);
        return $this->render('Admin/Comptabilite/comptabilite-debit.html.twig', [
          'ventes'  => $commandes,
          'reglements'  => $reglements,
        ]);
    }
}
