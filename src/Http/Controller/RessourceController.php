<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Ressource\Service\RessourceService;
use App\Domain\Consolidation\Repository\SessionConsolidationRepositoryInterface;
use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Parcours\Enum\TypeRessource;
use App\Domain\Parcours\Repository\ParcoursRepositoryInterface;
use App\Domain\Parcours\Repository\RessourceRepositoryInterface;
use App\Domain\Shared\Entity\User;
use App\Http\Voter\ParcoursVoter;
use App\Http\Voter\RessourceVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_USER')]
#[Route('/ressource')]
class RessourceController extends AbstractController
{
    #[Route('/{id}', name: 'app_ressource_show')]
    public function show(
        string $id,
        RessourceRepositoryInterface $ressourceRepository,
        SessionConsolidationRepositoryInterface $sessionRepository,
    ): Response {
        $ressource = $ressourceRepository->findById(Uuid::fromString($id));
        if ($ressource === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(RessourceVoter::VIEW, $ressource);

        /** @var User $user */
        $user = $this->getUser();

        $derniereSession = $sessionRepository->findLastCompleteForRessource($ressource);

        $sessionEnCours = null;
        foreach ($sessionRepository->findByRessourceForUser($ressource->getId(), $user) as $s) {
            if (in_array($s->getStatut()->value, ['pret', 'en_attente', 'generation'], true)) {
                $sessionEnCours = $s;
                break;
            }
        }

        return $this->render('ressource/show.html.twig', [
            'ressource'       => $ressource,
            'derniereSession' => $derniereSession,
            'sessionEnCours'  => $sessionEnCours,
        ]);
    }

    #[Route('/parcours/{id}/ajouter', name: 'app_ressource_ajouter', methods: ['POST'])]
    public function ajouter(
        string $id,
        Request $request,
        ParcoursRepositoryInterface $parcoursRepository,
        RessourceRepositoryInterface $ressourceRepository,
        RessourceService $ressourceService,
    ): Response {
        $parcours = $parcoursRepository->findById(Uuid::fromString($id));
        if ($parcours === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(ParcoursVoter::EDIT, $parcours);

        if (!$this->isCsrfTokenValid('add-ressource-' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'flash.csrf_error');
            return $this->redirectToRoute('app_parcours_show', ['id' => $id]);
        }

        $type  = TypeRessource::tryFrom((string) $request->request->get('type', 'COURS')) ?? TypeRessource::COURS;
        $duree = (int) $request->request->get('duree', 0) ?: null;
        $ordre = $parcours->getRessources()->count() + 1;

        $ressource = new Ressource(
            parcours: $parcours,
            titre:    (string) $request->request->get('titre', ''),
            type:     $type,
            ordre:    $ordre,
            url:      (string) $request->request->get('url') ?: null,
            source:   null,
            description: null,
            dureeEstimeeMinutes: $duree,
        );
        $pomodoros = $ressourceService->calculerPomodorosSuggeres($duree, $type);
        $ressource->setPomodorosSuggeres($pomodoros);
        $ressourceRepository->save($ressource, true);

        $this->addFlash('success', 'flash.ressource_added');

        return $this->redirectToRoute('app_parcours_show', ['id' => $id]);
    }

    #[Route('/{id}/demarrer', name: 'app_ressource_demarrer', methods: ['POST'])]
    public function demarrer(
        string $id,
        RessourceRepositoryInterface $ressourceRepository,
        RessourceService $ressourceService,
    ): Response {
        $ressource = $ressourceRepository->findById(Uuid::fromString($id));
        if ($ressource === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(RessourceVoter::EDIT, $ressource);

        /** @var User $user */
        $user = $this->getUser();
        $ressourceService->passerEnCours($ressource, $user);

        return $this->redirectToRoute('app_ressource_show', ['id' => $id]);
    }
}
