<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/users')]
final class UserController extends AbstractController
{
    #[Route('', methods: ['POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        // Vérifier que le JSON est valide
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'JSON invalide',
                'message' => json_last_error_msg()
            ], 400);
        }

        $user = new User();
        $user->setEmail($data['email'] ?? null);
        $user->setUsername($data['username'] ?? null);
        
        // Validation AVANT le hashage du mot de passe
        $plainPassword = $data['password'] ?? null;
        $user->setPassword($plainPassword); // Temporairement pour la validation
        
        $errors = $validator->validate($user);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            
            return $this->json([
                'error' => 'Validation échouée',
                'violations' => $errorMessages
            ], 400);
        }
        
        // Hash le mot de passe après validation
        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        
        $entityManager->persist($user);
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
            ]
        ], 201);
    }
}
