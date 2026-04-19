<?php

namespace App\Controller\Community;

use App\Entity\CommunityGroup;
use App\Entity\GroupMember;
use App\Entity\User;
use App\Form\CommunityGroupType;
use App\Repository\CommunityGroupRepository;
use App\Repository\GroupMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/community/groups')]
#[IsGranted('ROLE_USER')]
class CommunityGroupController extends AbstractController
{
    #[Route('', name: 'app_community_groups', methods: ['GET'])]
    public function index(CommunityGroupRepository $groupRepo): Response
    {
        return $this->render('community/groups/index.html.twig', [
            'groups' => $groupRepo->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'app_community_group_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $me = $this->requireUser();
        $group = new CommunityGroup();
        $group->setCreator($me);

        $form = $this->createForm(CommunityGroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($group);

            // Ajouter le créateur comme membre ADMIN
            $member = new GroupMember();
            $member->setGroup($group);
            $member->setUser($me);
            $member->setRole(GroupMember::ROLE_ADMIN);
            $em->persist($member);

            $em->flush();
            $this->addFlash('success', 'Groupe créé avec succès.');
            return $this->redirectToRoute('app_community_group_show', ['id' => $group->getId()]);
        }

        return $this->render('community/groups/form.html.twig', [
            'form' => $form,
            'group' => $group,
            'is_new' => true,
        ]);
    }

    #[Route('/{id}', name: 'app_community_group_show', methods: ['GET'])]
    public function show(CommunityGroup $group, GroupMemberRepository $memberRepo): Response
    {
        $me = $this->requireUser();
        $membership = $memberRepo->findMembership($group->getId(), $me);
        $members = $memberRepo->findByGroup($group->getId());

        return $this->render('community/groups/show.html.twig', [
            'group' => $group,
            'membership' => $membership,
            'members' => $members,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_community_group_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CommunityGroup $group, EntityManagerInterface $em): Response
    {
        if ($group->getCreator()->getId() !== $this->requireUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CommunityGroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Groupe mis à jour.');
            return $this->redirectToRoute('app_community_group_show', ['id' => $group->getId()]);
        }

        return $this->render('community/groups/form.html.twig', [
            'form' => $form,
            'group' => $group,
            'is_new' => false,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_community_group_delete', methods: ['POST'])]
    public function delete(Request $request, CommunityGroup $group, EntityManagerInterface $em): Response
    {
        if ($group->getCreator()->getId() !== $this->requireUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete_group_' . $group->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_community_groups');
        }

        $em->remove($group);
        $em->flush();
        $this->addFlash('success', 'Groupe supprimé.');

        return $this->redirectToRoute('app_community_groups');
    }

    #[Route('/{id}/join', name: 'app_community_group_join', methods: ['POST'])]
    public function join(
        Request $request,
        CommunityGroup $group,
        EntityManagerInterface $em,
        GroupMemberRepository $memberRepo,
    ): Response {
        $me = $this->requireUser();

        if (!$this->isCsrfTokenValid('join_group_' . $group->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_community_group_show', ['id' => $group->getId()]);
        }

        if ($memberRepo->isMember($group->getId(), $me)) {
            $this->addFlash('info', 'Vous êtes déjà membre de ce groupe.');
            return $this->redirectToRoute('app_community_group_show', ['id' => $group->getId()]);
        }

        $member = new GroupMember();
        $member->setGroup($group);
        $member->setUser($me);
        $member->setRole(GroupMember::ROLE_MEMBER);
        $em->persist($member);

        $group->setMemberCount($group->getMemberCount() + 1);
        $em->flush();

        $this->addFlash('success', 'Vous avez rejoint le groupe.');
        return $this->redirectToRoute('app_community_group_show', ['id' => $group->getId()]);
    }

    #[Route('/{id}/leave', name: 'app_community_group_leave', methods: ['POST'])]
    public function leave(
        Request $request,
        CommunityGroup $group,
        EntityManagerInterface $em,
        GroupMemberRepository $memberRepo,
    ): Response {
        $me = $this->requireUser();

        if (!$this->isCsrfTokenValid('leave_group_' . $group->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_community_group_show', ['id' => $group->getId()]);
        }

        $membership = $memberRepo->findMembership($group->getId(), $me);
        if (!$membership) {
            $this->addFlash('warning', 'Vous n\'êtes pas membre de ce groupe.');
            return $this->redirectToRoute('app_community_group_show', ['id' => $group->getId()]);
        }

        if ($group->getCreator()->getId() === $me->getId()) {
            $this->addFlash('warning', 'Le créateur ne peut pas quitter le groupe. Supprimez-le à la place.');
            return $this->redirectToRoute('app_community_group_show', ['id' => $group->getId()]);
        }

        $em->remove($membership);
        $group->setMemberCount(max(1, $group->getMemberCount() - 1));
        $em->flush();

        $this->addFlash('success', 'Vous avez quitté le groupe.');
        return $this->redirectToRoute('app_community_groups');
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        return $user;
    }
}
