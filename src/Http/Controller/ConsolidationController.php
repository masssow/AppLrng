<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Consolidation\Service\ConsolidationService;
use App\Domain\Consolidation\Repository\ExerciceRepositoryInterface;
use App\Domain\Consolidation\Repository\QuestionRepositoryInterface;
use App\Domain\Consolidation\Repository\SessionConsolidationRepositoryInterface;
use App\Domain\Parcours\Repository\RessourceRepositoryInterface;
use App\Domain\Shared\Entity\User;
use App\Http\Form\RenduType;
use App\Http\Form\TraceApprentissageType;
use App\Http\Voter\RessourceVoter;
use App\Http\Voter\SessionConsolidationVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_USER')]
#[Route('/consolidation')]
class ConsolidationController extends AbstractController
{
    #[Route('/ressource/{id}/initier', name: 'app_consolidation_initier', methods: ['GET', 'POST'])]
    public function initier(
        string $id,
        Request $request,
        RessourceRepositoryInterface $ressourceRepository,
        ConsolidationService $consolidationService,
    ): Response {
        $ressource = $ressourceRepository->findById(Uuid::fromString($id));
        if ($ressource === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(RessourceVoter::VIEW, $ressource);

        $form = $this->createForm(TraceApprentissageType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $session = $consolidationService->initier($ressource, $user, $form->getData());

            $this->addFlash('success', 'flash.consolidation_initiated');

            return $this->redirectToRoute('app_consolidation_show', ['id' => (string) $session->getId()]);
        }

        return $this->render('consolidation/initier.html.twig', [
            'form'      => $form,
            'ressource' => $ressource,
        ]);
    }

    #[Route('/session/{id}', name: 'app_consolidation_show', methods: ['GET', 'POST'])]
    public function show(
        string $id,
        Request $request,
        SessionConsolidationRepositoryInterface $sessionRepository,
        QuestionRepositoryInterface $questionRepository,
        ExerciceRepositoryInterface $exerciceRepository,
        ConsolidationService $consolidationService,
    ): Response {
        $session = $sessionRepository->findById(Uuid::fromString($id));
        if ($session === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(SessionConsolidationVoter::VIEW, $session);

        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('consolidation-' . $id, (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'flash.csrf_error');
                return $this->redirectToRoute('app_consolidation_show', ['id' => $id]);
            }

            if ($request->request->get('question_id')) {
                $question = $questionRepository->findById(
                    Uuid::fromString((string) $request->request->get('question_id'))
                );
                if ($question !== null && $question->getSession() === $session) {
                    $consolidationService->soumettreReponseQuestion(
                        $question,
                        (string) $request->request->get('reponse', ''),
                        $user,
                    );
                }
            } elseif ($request->request->get('exercice_id')) {
                $exercice = $exerciceRepository->findById(
                    Uuid::fromString((string) $request->request->get('exercice_id'))
                );
                if ($exercice !== null && $exercice->getSession() === $session) {
                    $consolidationService->soumettreRenduExercice(
                        $exercice,
                        (string) $request->request->get('rendu', ''),
                        $user,
                    );
                }
            }

            return $this->redirectToRoute('app_consolidation_show', ['id' => $id]);
        }

        return $this->render('consolidation/show.html.twig', ['session' => $session]);
    }

    #[Route('/session/{id}/terminer', name: 'app_consolidation_terminer', methods: ['POST'])]
    public function terminer(
        string $id,
        SessionConsolidationRepositoryInterface $sessionRepository,
        ConsolidationService $consolidationService,
    ): Response {
        $session = $sessionRepository->findById(Uuid::fromString($id));
        if ($session === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(SessionConsolidationVoter::VIEW, $session);

        $consolidationService->terminerSession($session);
        $this->addFlash('success', 'flash.session_completed');

        return $this->redirectToRoute('app_parcours_show', [
            'id' => (string) $session->getRessource()->getParcours()->getId(),
        ]);
    }
}
