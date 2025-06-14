<?php

namespace App\Controller;

use App\Entity\Roles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\Entity\Videojuegos;

#[Route('/api/videojuegos', name: 'videojuegos_')]
final class VideojuegosController extends AbstractController
{
    #[Route('/listarVideojuegos', name: 'app_listar_videojuegos', methods: ['POST'])]
    public function listarVideojuegos(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $jwtManager->parse($token); // Lanza excepción si el token no es válido
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $videojuegos = $em->getRepository(Videojuegos::class)->findAll();
        $result = [];
        foreach ($videojuegos as $videojuego) {
            $imagenes = [];
            foreach ($videojuego->getImagenes() as $img) {
                $imagenes[] = [
                    'url' => $img->getUrl(),
                    'portada' => $img->isPortada()
                ];
            }
            $categorias = [];
            foreach ($videojuego->getCategoria() as $cat) {
                $categorias[] = $cat->getNombre();
            }
            $result[] = [
                'id' => $videojuego->getId(),
                'nombre' => $videojuego->getNombre(),
                'deleted' => $videojuego->isDeleted(),
                'precio' => $videojuego->getPrecio(),
                'fecha_lanzamiento' => $videojuego->getFechaLanzamiento()->format('d/m/Y'),
                'stock' => $videojuego->getStock(),
                'imagenes' => $imagenes,
                'categorias' => $categorias,
                'plataforma' => $videojuego->getPlataforma()?->getNombre(),
            ];
        }
        return new JsonResponse($result);
    }
}