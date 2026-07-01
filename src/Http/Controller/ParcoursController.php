<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Parcours\Service\ParcoursService;
use App\Domain\Consolidation\Repository\SessionConsolidationRepositoryInterface;
use App\Domain\Parcours\Enum\StatutParcours;
use App\Domain\Parcours\Enum\StatutRessource;
use App\Domain\Parcours\Repository\ParcoursRepositoryInterface;
use App\Domain\Shared\Entity\User;
use App\Http\Form\ParcoursType;
use App\Http\Voter\ParcoursVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_USER')]
#[Route('/parcours')]
class ParcoursController extends AbstractController
{
    #[Route('', name: 'app_parcours_index')]
    public function index(ParcoursRepositoryInterface $parcoursRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('parcours/index.html.twig', [
            'parcours' => $parcoursRepository->findByUserOrderedByDate($user),
        ]);
    }

    #[Route('/nouveau', name: 'app_parcours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ParcoursService $parcoursService): Response
    {
        $form = $this->createForm(ParcoursType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $data = $form->getData();

            $parcours = $parcoursService->initier(
                $user,
                $data['titre'],
                $data['objectif'],
                $data['domaine'],
                $data['niveau'],
                $data['dureeCibleSemaines'],
                [],
            );

            $this->addFlash('success', 'flash.parcours_created');

            return $this->redirectToRoute('app_parcours_show', ['id' => (string) $parcours->getId()]);
        }

        return $this->render('parcours/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'app_parcours_show')]
    public function show(
        string $id,
        ParcoursRepositoryInterface $parcoursRepository,
        SessionConsolidationRepositoryInterface $sessionRepository,
    ): Response {
        $parcours = $parcoursRepository->findById(Uuid::fromString($id));
        if ($parcours === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(ParcoursVoter::VIEW, $parcours);

        $dernieresSessionsMap = [];
        foreach ($parcours->getRessources() as $ressource) {
            if ($ressource->getStatut() === StatutRessource::CONSOLIDEE) {
                $session = $sessionRepository->findLastCompleteForRessource($ressource);
                if ($session !== null) {
                    $dernieresSessionsMap[(string) $ressource->getId()] = $session;
                }
            }
        }

        return $this->render('parcours/show.html.twig', [
            'parcours'             => $parcours,
            'dernieresSessionsMap' => $dernieresSessionsMap,
        ]);
    }

    #[Route('/{id}/structurer', name: 'app_parcours_structurer', methods: ['POST'])]
    public function structurer(
        string $id,
        Request $request,
        ParcoursRepositoryInterface $parcoursRepository,
        ParcoursService $parcoursService,
    ): Response {
        $parcours = $parcoursRepository->findById(Uuid::fromString($id));
        if ($parcours === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(ParcoursVoter::EDIT, $parcours);

        if (!$this->isCsrfTokenValid('structurer-' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if ($parcours->getStatut() !== StatutParcours::BROUILLON) {
            $this->addFlash('warning', 'Ce parcours est déjà structuré ou en cours de structuration.');
            return $this->redirectToRoute('app_parcours_show', ['id' => $id]);
        }

        if ($parcours->getRessources()->count() === 0) {
            $this->addFlash('error', 'Ajoutez au moins une ressource avant de lancer la structuration.');
            return $this->redirectToRoute('app_parcours_show', ['id' => $id]);
        }

        $parcoursService->lancerStructuration($parcours);

        $this->addFlash('success', 'L\'IA structure votre parcours en arrière-plan.');
        return $this->redirectToRoute('app_parcours_show', ['id' => $id]);
    }

    #[Route('/{id}/supprimer', name: 'app_parcours_delete', methods: ['POST'])]
    public function delete(string $id, Request $request, ParcoursService $parcoursService, ParcoursRepositoryInterface $parcoursRepository): Response
    {
        $parcours = $parcoursRepository->findById(Uuid::fromString($id));
        if ($parcours === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(ParcoursVoter::DELETE, $parcours);

        if ($this->isCsrfTokenValid('delete-parcours-' . $id, (string) $request->request->get('_token'))) {
            /** @var User $user */
            $user = $this->getUser();
            $parcoursService->supprimer($parcours, $user);
            $this->addFlash('success', 'flash.parcours_deleted');
        }

        return $this->redirectToRoute('app_parcours_index');
    }
}
