<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Shared\Entity\User;
use App\Domain\Shared\Enum\NiveauMaitrise;
use App\Domain\Shared\Repository\UserRepositoryInterface;

use App\Http\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepositoryInterface $userRepository,
    ): Response {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $user = new User(
                email:  $data['email'],
                prenom: $data['prenom'],
                nom:    $data['nom'],
                niveau: NiveauMaitrise::DEBUTANT,
            );
            $user->setPassword($passwordHasher->hashPassword($user, $data['plainPassword']));
            $userRepository->save($user, true);

            $this->addFlash('success', 'flash.registration_success');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
