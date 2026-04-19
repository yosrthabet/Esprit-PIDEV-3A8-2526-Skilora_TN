<?php

namespace App\Controller;

use App\Entity\CommunityEvent;
use App\Entity\EventRsvp;
use App\Entity\User;
use App\Form\CommunityEventType;
use App\Repository\CommunityEventRepository;
use App\Repository\EventRsvpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/community/events')]
#[IsGranted('ROLE_USER')]
class CommunityEventController extends AbstractController
{
    #[Route('', name: 'app_community_events', methods: ['GET'])]
    public function index(CommunityEventRepository $eventRepo): Response
    {
        return $this->render('community/events/index.html.twig', [
            'events' => $eventRepo->findAll(),
            'upcoming' => $eventRepo->findUpcoming(),
        ]);
    }

    #[Route('/new', name: 'app_community_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $event = new CommunityEvent();
        $event->setOrganizer($this->requireUser());

        $form = $this->createForm(CommunityEventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'Événement créé avec succès.');
            return $this->redirectToRoute('app_community_events');
        }

        return $this->render('community/events/form.html.twig', [
            'form' => $form,
            'event' => $event,
            'is_new' => true,
        ]);
    }

    #[Route('/{id}', name: 'app_community_event_show', methods: ['GET'])]
    public function show(CommunityEvent $event, EventRsvpRepository $rsvpRepo): Response
    {
        $me = $this->requireUser();
        $myRsvp = $rsvpRepo->findByEventAndUser($event->getId(), $me);

        return $this->render('community/events/show.html.twig', [
            'event' => $event,
            'my_rsvp' => $myRsvp,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_community_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CommunityEvent $event, EntityManagerInterface $em): Response
    {
        if ($event->getOrganizer()->getId() !== $this->requireUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CommunityEventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Événement mis à jour.');
            return $this->redirectToRoute('app_community_event_show', ['id' => $event->getId()]);
        }

        return $this->render('community/events/form.html.twig', [
            'form' => $form,
            'event' => $event,
            'is_new' => false,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_community_event_delete', methods: ['POST'])]
    public function delete(Request $request, CommunityEvent $event, EntityManagerInterface $em): Response
    {
        if ($event->getOrganizer()->getId() !== $this->requireUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete_event_' . $event->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_community_events');
        }

        $em->remove($event);
        $em->flush();
        $this->addFlash('success', 'Événement supprimé.');

        return $this->redirectToRoute('app_community_events');
    }

    #[Route('/{id}/rsvp', name: 'app_community_event_rsvp', methods: ['POST'])]
    public function rsvp(
        Request $request,
        CommunityEvent $event,
        EntityManagerInterface $em,
        EventRsvpRepository $rsvpRepo,
    ): Response {
        $me = $this->requireUser();

        if (!$this->isCsrfTokenValid('rsvp_event_' . $event->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_community_event_show', ['id' => $event->getId()]);
        }

        $status = $request->request->getString('status', EventRsvp::STATUS_GOING);
        $existing = $rsvpRepo->findByEventAndUser($event->getId(), $me);

        if ($existing) {
            if ($status === 'CANCEL') {
                $em->remove($existing);
                $event->setCurrentAttendees(max(0, $event->getCurrentAttendees() - 1));
            } else {
                $existing->setStatus($status);
            }
        } else {
            if ($event->isFull()) {
                $this->addFlash('warning', 'L\'événement est complet.');
                return $this->redirectToRoute('app_community_event_show', ['id' => $event->getId()]);
            }

            $rsvp = new EventRsvp();
            $rsvp->setEvent($event);
            $rsvp->setUser($me);
            $rsvp->setStatus($status);
            $em->persist($rsvp);
            $event->setCurrentAttendees($event->getCurrentAttendees() + 1);
        }

        $em->flush();
        $this->addFlash('success', 'Votre participation a été enregistrée.');

        return $this->redirectToRoute('app_community_event_show', ['id' => $event->getId()]);
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
