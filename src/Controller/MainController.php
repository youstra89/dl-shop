<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use App\Service\CheckConnectedUser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MainController extends AbstractController
{
  /**
   * @Route("/", name="homepage")
   */
    public function index(CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $user = $this->getUser();
        if($this->isGranted('ROLE_ADMIN'))
          return $this->redirectToRoute('panel.admin');

        return $this->render('base-user.html.twig');
    }

  /**
   * @Route("/admin", name="panel.admin")
   */
    public function index_panel_admin(CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');
        return $this->render('base.html.twig');
    }
}
