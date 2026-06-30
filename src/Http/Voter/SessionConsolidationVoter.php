<?php

declare(strict_types=1);

namespace App\Http\Voter;

use App\Domain\Consolidation\Entity\SessionConsolidation;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SessionConsolidationVoter extends Voter
{
    public const VIEW = 'SESSION_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::VIEW && $subject instanceof SessionConsolidation;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var SessionConsolidation $subject */
        return $subject->getRessource()->getParcours()->getUser() === $user;
    }
}
