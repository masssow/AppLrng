<?php

declare(strict_types=1);

namespace App\Http\Voter;

use App\Domain\Projet\Entity\ProjetFilRouge;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProjetFilRougeVoter extends Voter
{
    public const VIEW = 'PROJET_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::VIEW && $subject instanceof ProjetFilRouge;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var ProjetFilRouge $subject */
        return $subject->getParcours()->getUser() === $user;
    }
}
