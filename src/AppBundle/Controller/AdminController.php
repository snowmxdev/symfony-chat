<?php

namespace AppBundle\Controller;


use AppBundle\Utils\AdminPanel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AdminController extends Controller
{
    /**
     * @Route("/chat/admin/", name="chat_admin")
     *
     * Gets info about Users
     */
    public function adminAction(AdminPanel $adminPanel)
    {
        return $this->render('admin/index.html.twig', [
            'users' => $adminPanel->getAllUsers()
        ]);
    }

    /**
     * @Route("/chat/admin/change/{id}/{role}", name="chat_admin_change")
     *
     * Changes user's role
     */
    public function adminPromoteAction(int $id, string $role, AdminPanel $adminPanel)
    {
        $adminPanel->changeUsersRole($id, $role);

        return $this->redirectToRoute('chat_admin');
    }
}