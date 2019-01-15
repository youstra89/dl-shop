<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Route("/admin/users")
 * @Security("has_role('ROLE_ADMIN')")
 */
class AdminUserController extends AbstractController
{
  /**
   * @Route("/", name="users", methods="GET")
   */
  public function index(Request $request, ObjectManager $manager): Response
  {
    // $em = $this->getDoctrine()->getManager();
    $repoUser = $manager->getRepository(User::class);

    $users = $repoUser->findAll();

    return $this->render('Admin/User/index.html.twig', [
      'users' => $users,
    ]);
  }

  /**
   * @Route("/information/{id}", name="user.info", methods="GET", requirements={"id"="\d+"})
   * @Security("has_role('ROLE_ADMIN')")
   * @param User $user
   */
  public function user_information(Request $request, User $user): Response
  {
    $em = $this->getDoctrine()->getManager();
    return $this->render('Admin/User/user-info.html.twig', [
      'user'  => $user
    ]);
  }
}
