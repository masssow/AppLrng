<?php

declare(strict_types=1);

namespace App\Http\Voter;

use App\Domain\Consolidation\Entity\TraceApprentissage;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TraceApprentissageVoter extends Voter
{
    public const CREATE = 'TRACE_CREATE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::CREATE && $subject instanceof TraceApprentissage;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var TraceApprentissage $subject */
        return $subject->getRessource()->getParcours()->getUser() === $user;
    }
}
