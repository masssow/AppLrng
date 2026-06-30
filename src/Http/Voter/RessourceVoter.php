<?php

declare(strict_types=1);

namespace App\Http\Voter;

use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RessourceVoter extends Voter
{
    public const VIEW = 'RESSOURCE_VIEW';
    public const EDIT = 'RESSOURCE_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true)
            && $subject instanceof Ressource;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Ressource $subject */
        return $subject->getParcours()->getUser() === $user;
    }
}
