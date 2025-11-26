<?php

namespace App\Security\Voter;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProductVoter extends Voter
{
    public const CREATE = 'PRODUCT_CREATE';
    public const EDIT = 'PRODUCT_EDIT';
    public const DELETE = 'PRODUCT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Le voter ne s'applique que pour les attributs définis ci-dessus
        if (!in_array($attribute, [self::CREATE, self::EDIT, self::DELETE])) {
            return false;
        }

        // Le voter ne s'applique que sur des objets Product
        if (!$subject instanceof Product) {
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

        // Logique selon l'action
        return match($attribute) {
            self::CREATE => $this->canCreate($user),
            self::EDIT => $this->canEdit($user),
            self::DELETE => $this->canDelete($user),
            default => false,
        };
    }

    private function canCreate(User $user): bool
    {
        // Un admin peut créer des produits
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canEdit(User $user): bool
    {
        // Un admin peut éditer des produits
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(User $user): bool
    {
        // Un super admin peut supprimer des produits
        return in_array('ROLE_SUPER_ADMIN', $user->getRoles());
    }
}