<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Serializer\SerializerInterface;
use App\Dto\ProductCreateDto;
use App\Service\FileUploader;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

#[Route('/ping', name: 'app_ping_')]
final class PingController extends AbstractController
{
    #[Route('', name: 'ping', methods: ['GET'])]
    public function list(ProductRepository $productRepository): JsonResponse
    {
        $response = new JsonResponse();
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent(json_encode([
            'ping' => 'pong'
        ]));

        return $response;
    }
}
