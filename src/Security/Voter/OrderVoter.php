<?php

namespace App\Security\Voter;

use App\Entity\Order;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrderVoter extends Voter
{
    public const LIST = 'ORDER_LIST';
    public const SHOW = 'ORDER_SHOW';
    public const EDIT = 'ORDER_EDIT';
    public const DELETE = 'ORDER_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::LIST, self::SHOW, self::EDIT, self::DELETE], true)) {
            return false;
        }

        if (!$subject instanceof Order) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match($attribute) {
            self::LIST => $this->canShowList($user),
            self::SHOW => $this->canShow($user, $subject),
            self::EDIT => $this->canEdit($user),
            self::DELETE => $this->canDelete($user),
            default => false,
        };
    }

    private function canShowList(User $user): bool
    {
        
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canShow(User $user, Order $order): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles()) || $order->getUser() === $user;
    }

    private function canEdit(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(User $user): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $user->getRoles());
    }
}
