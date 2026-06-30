<?php

declare(strict_types=1);

namespace App\Http\Voter;

use App\Domain\Parcours\Entity\Parcours;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ParcoursVoter extends Voter
{
    public const VIEW   = 'PARCOURS_VIEW';
    public const EDIT   = 'PARCOURS_EDIT';
    public const DELETE = 'PARCOURS_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Parcours;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Parcours $subject */
        return $subject->getUser() === $user;
    }
}
