<?php

namespace App\Security\Voter;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    public const VIEW = 'POST_VIEW';
    public const EDIT = 'POST_EDIT';
    public const DELETE = 'POST_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var Post $post */
        $post = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView(),
            self::EDIT => $this->canEdit($post, $token),
            self::DELETE => $this->canDelete($post, $token),
            default => false,
        };
    }

    private function canView(): bool
    {
        // Posts are public - anyone can view them
        return true;
    }

    private function canEdit(Post $post, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // User must be authenticated
        if (!$user instanceof User) {
            return false;
        }

        // Author can edit their own post OR admin can edit any post
        return $post->getAuthor()->getId() === $user->getId()
            || in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(Post $post, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // User must be authenticated
        if (!$user instanceof User) {
            return false;
        }

        // Author can delete their own post OR admin can delete any post
        return $post->getAuthor()->getId() === $user->getId()
            || in_array('ROLE_ADMIN', $user->getRoles());
    }
}
