<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserEditType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @Route("/users/gererUsers", name="gererUsers")
     */
    public function gererUsers(UserRepository $userRepository, Request $request)
    {
        $users = $userRepository->findAll();
        return $this->render('admin/gererUsers.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * @Route("/users/gererUsers/edit/{id}", name="editUser")
     *
     */
    public function editUser(UserRepository $userRepository, Request $request, EntityManagerInterface $entityManager)
    {
        $user = $userRepository->find($request->get('id'));
        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
        }
        $repository = $entityManager->getRepository(User::class)->findAll();
        return $this->render('admin/edit.html.twig', [
            'form' => $form->createView(),
            'users' => $repository,
        ]);
    }

    /**
     * @Route("/users/gererUsers/remove/{id}", name="removeUser")
     */
    public function removeUser(UserRepository $userRepository, EntityManagerInterface $entityManager, Request $request)
    {

        $user = $userRepository->find($request->get('id'));
        $votes = $user->getVotes();
        foreach ($votes as $vote) {
            $entityManager->remove($vote);
            $entityManager->flush();
        }
        $entityManager->remove($user);
        $entityManager->flush();
        $this->addFlash('notice', 'Your changes were saved!');
        return $this->redirectToRoute('gererUsers');
    }
}
