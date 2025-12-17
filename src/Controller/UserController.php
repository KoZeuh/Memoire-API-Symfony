<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/user', name: 'app_user_')]
final class UserController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();

        return $this->json($users, Response::HTTP_OK, [], [
            'groups' => ['user:read']
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($user, Response::HTTP_OK, [], [
            'groups' => ['user:read']
        ]);
    }

    #[Route('/login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Cette méthode ne sera jamais appelée car l'authentification est gérée par le firewall
        // Elle sert uniquement de point d'entrée pour la route /user/login
        // Le firewall 'login' dans security.yaml intercepte cette route et gère l'authentification
        // En cas de succès, lexik_jwt_authentication.handler.authentication_success retourne le token
        return $this->json([
            'message' => 'Cette route est gérée par le firewall pour l\'authentification.'
        ]);
    }

    #[Route('/register', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload(serializationContext: ['groups' => ['user:create']])] User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur créé avec succès'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        User $user,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            [
                'object_to_populate' => $user,
                'groups' => ['user:update']
            ]
        );
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $payload = $request->toArray();
        if (isset($payload['password'])) {
            $user->setPassword(
                $passwordHasher->hashPassword($user, $user->getPassword())
            );
        }

        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur mis à jour avec succès'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur supprimé avec succès'], Response::HTTP_OK);
    }
}
