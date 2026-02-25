<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const VIEW = 'USER_VIEW';
    public const EDIT = 'USER_EDIT';
    public const DELETE = 'USER_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        // User must be authenticated
        if (!$currentUser instanceof User) {
            return false;
        }

        /** @var User $targetUser */
        $targetUser = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($targetUser, $currentUser),
            self::EDIT => $this->canEdit($targetUser, $currentUser),
            self::DELETE => $this->canDelete($targetUser, $currentUser),
            default => false,
        };
    }

    private function canView(User $targetUser, User $currentUser): bool
    {
        // User can view their own profile OR admin can view any profile
        return $targetUser->getId() === $currentUser->getId()
            || in_array('ROLE_ADMIN', $currentUser->getRoles());
    }

    private function canEdit(User $targetUser, User $currentUser): bool
    {
        // User can edit their own profile OR admin can edit any profile
        return $targetUser->getId() === $currentUser->getId()
            || in_array('ROLE_ADMIN', $currentUser->getRoles());
    }

    private function canDelete(User $targetUser, User $currentUser): bool
    {
        // User can delete their own account OR admin can delete any account
        return $targetUser->getId() === $currentUser->getId()
            || in_array('ROLE_ADMIN', $currentUser->getRoles());
    }
}
