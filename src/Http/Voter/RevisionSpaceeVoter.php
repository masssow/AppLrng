<?php

declare(strict_types=1);

namespace App\Http\Voter;

use App\Domain\Revision\Entity\RevisionSpacee;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RevisionSpaceeVoter extends Voter
{
    public const COMPLETE = 'REVISION_COMPLETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::COMPLETE && $subject instanceof RevisionSpacee;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var RevisionSpacee $subject */
        return $subject->getUser() === $user;
    }
}
