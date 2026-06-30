<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Revision\Service\RevisionService;
use App\Domain\Revision\Repository\RevisionSpaceeRepositoryInterface;
use App\Domain\Shared\Entity\User;
use App\Http\Voter\RevisionSpaceeVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_USER')]
#[Route('/revision')]
class RevisionController extends AbstractController
{
    #[Route('', name: 'app_revision_index')]
    public function index(RevisionSpaceeRepositoryInterface $revisionRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('revision/index.html.twig', [
            'revisions' => $revisionRepository->findPendingForUser($user, new \DateTime()),
        ]);
    }

    #[Route('/{id}/completer', name: 'app_revision_completer', methods: ['POST'])]
    public function completer(
        string $id,
        Request $request,
        RevisionSpaceeRepositoryInterface $revisionRepository,
        RevisionService $revisionService,
    ): Response {
        $revision = $revisionRepository->findById(Uuid::fromString($id));
        if ($revision === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(RevisionSpaceeVoter::COMPLETE, $revision);

        $score = (int) $request->request->get('score', 3);
        $revisionService->completer($revision, $score);
        $this->addFlash('success', 'flash.revision_completed');

        return $this->redirectToRoute('app_revision_index');
    }
}
