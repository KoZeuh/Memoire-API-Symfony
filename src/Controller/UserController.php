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
    #[Route('/register', methods: ['POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

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
                    'message' => 'Validation échouée',
                    'violations' => $errorMessages
                ], 400);
            }
            
            // Hash le mot de passe après validation
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            
            $entityManager->persist($user);
            $entityManager->flush();
            
            return $this->json([
                'message' => 'Utilisateur créé avec succès'
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Cette méthode ne sera jamais appelée car l'authentification est gérée par le firewall
        // Elle sert uniquement de point d'entrée pour la route /api/users/login
        // Le firewall 'login' dans security.yaml intercepte cette route et gère l'authentification
        // En cas de succès, lexik_jwt_authentication.handler.authentication_success retourne le token
        return $this->json([
            'message' => 'Cette route est gérée par le firewall pour l\'authentification.'
        ]);
    }
}
