<?php

declare(strict_types=1);

namespace App\Http\Voter;

use App\Domain\Projet\Entity\MicroEtape;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MicroEtapeVoter extends Voter
{
    public const INTERACT = 'MICRO_ETAPE_INTERACT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::INTERACT && $subject instanceof MicroEtape;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var MicroEtape $subject */
        return $subject->getProjet()->getParcours()->getUser() === $user;
    }
}
