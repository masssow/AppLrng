<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Dashboard\Service\DashboardService;
use App\Domain\Shared\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(DashboardService $dashboardService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('dashboard/index.html.twig', [
            'actionAReprendre' => $dashboardService->getActionAReprendre($user),
            'revisionsDuJour'  => $dashboardService->getRevisionsDuJour($user),
            'streak'           => $dashboardService->getStreak($user),
            'scoreAgrege'      => $dashboardService->getScoreAgrege($user),
            'parcoursActifs'   => $dashboardService->getParcoursActifs($user),
        ]);
    }
}
