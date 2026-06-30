<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Projet\Service\MicroEtapeService;
use App\Domain\Parcours\Repository\ParcoursRepositoryInterface;
use App\Domain\Projet\Repository\MicroEtapeRepositoryInterface;
use App\Domain\Shared\Entity\User;
use App\Http\Form\RenduType;
use App\Http\Voter\MicroEtapeVoter;
use App\Http\Voter\ParcoursVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_USER')]
#[Route('/projet')]
class ProjetController extends AbstractController
{
    #[Route('/parcours/{id}', name: 'app_projet_show')]
    public function show(string $id, ParcoursRepositoryInterface $parcoursRepository): Response
    {
        $parcours = $parcoursRepository->findById(Uuid::fromString($id));
        if ($parcours === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(ParcoursVoter::VIEW, $parcours);

        return $this->render('projet/show.html.twig', [
            'projet' => $parcours->getProjetFilRouge(),
            'parcours' => $parcours,
        ]);
    }

    #[Route('/etape/{id}/demarrer', name: 'app_projet_etape_demarrer', methods: ['POST'])]
    public function demarrer(
        string $id,
        MicroEtapeRepositoryInterface $microEtapeRepository,
        MicroEtapeService $microEtapeService,
    ): Response {
        $etape = $microEtapeRepository->findById(Uuid::fromString($id));
        if ($etape === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(MicroEtapeVoter::INTERACT, $etape);

        /** @var User $user */
        $user = $this->getUser();
        $microEtapeService->demarrer($etape, $user);

        return $this->redirectToRoute('app_projet_show', [
            'id' => (string) $etape->getProjet()->getParcours()->getId(),
        ]);
    }

    #[Route('/etape/{id}/rendu', name: 'app_projet_etape_rendu', methods: ['GET', 'POST'])]
    public function rendu(
        string $id,
        Request $request,
        MicroEtapeRepositoryInterface $microEtapeRepository,
        MicroEtapeService $microEtapeService,
    ): Response {
        $etape = $microEtapeRepository->findById(Uuid::fromString($id));
        if ($etape === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(MicroEtapeVoter::INTERACT, $etape);

        $form = $this->createForm(RenduType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $microEtapeService->soumettreRendu($etape, $form->get('rendu')->getData(), $user);
            $this->addFlash('success', 'flash.rendu_submitted');

            return $this->redirectToRoute('app_projet_show', [
                'id' => (string) $etape->getProjet()->getParcours()->getId(),
            ]);
        }

        return $this->render('projet/rendu.html.twig', ['form' => $form, 'etape' => $etape]);
    }

    #[Route('/etape/{id}/piste', name: 'app_projet_etape_piste', methods: ['POST'])]
    public function piste(
        string $id,
        Request $request,
        MicroEtapeRepositoryInterface $microEtapeRepository,
        MicroEtapeService $microEtapeService,
    ): JsonResponse {
        $etape = $microEtapeRepository->findById(Uuid::fromString($id));
        if ($etape === null) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $this->denyAccessUnlessGranted(MicroEtapeVoter::INTERACT, $etape);

        /** @var User $user */
        $user    = $this->getUser();
        $blocage = (string) $request->request->get('blocage', '');
        $piste   = $microEtapeService->demanderPiste($etape, $blocage, $user);

        return new JsonResponse(['piste' => $piste]);
    }
}
