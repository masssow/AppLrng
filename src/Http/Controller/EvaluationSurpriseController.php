<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Consolidation\Repository\SessionConsolidationRepositoryInterface;
use App\Domain\Parcours\Repository\RessourceRepositoryInterface;
use App\Domain\Shared\Entity\User;
use App\Http\Voter\RessourceVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_USER')]
#[Route('/evaluation-surprise')]
class EvaluationSurpriseController extends AbstractController
{
    #[Route('/ressource/{id}', name: 'app_evaluation_surprise_show')]
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

        $derniereSession = $sessionRepository->findLastCompleteForRessource($ressource);

        return $this->render('evaluation_surprise/show.html.twig', [
            'ressource'      => $ressource,
            'derniereSession' => $derniereSession,
        ]);
    }
}
