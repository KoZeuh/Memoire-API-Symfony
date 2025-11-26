<?php

namespace App\Security\Voter;

use App\Entity\Order;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrderVoter extends Voter
{
    public const VIEW = 'ORDER_VIEW';
    public const EDIT = 'ORDER_EDIT';
    public const DELETE = 'ORDER_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Le voter ne s'applique que pour les attributs définis ci-dessus
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        // Le voter ne s'applique que sur des objets Order
        if (!$subject instanceof Order) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être connecté
        if (!$user instanceof User) {
            return false;
        }

        /** @var Order $order */
        $order = $subject;

        // Les admins ont tous les droits
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Logique selon l'action
        return match($attribute) {
            self::VIEW => $this->canView($order, $user),
            self::EDIT => $this->canEdit($order, $user),
            self::DELETE => $this->canDelete($order, $user),
            default => false,
        };
    }

    private function canView(Order $order, User $user): bool
    {
        // Un utilisateur peut voir ses propres commandes
        return $order->getUser()->getId() === $user->getId();
    }

    private function canEdit(Order $order, User $user): bool
    {
        // Un utilisateur peut modifier ses commandes uniquement si elles sont en pending
        return $order->getUser()->getId() === $user->getId() 
            && $order->getStatus() === 'pending';
    }

    private function canDelete(Order $order, User $user): bool
    {
        // Un utilisateur ne peut pas supprimer ses commandes
        return false;
    }
}