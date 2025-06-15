<?php

namespace App\Controller;

use App\Entity\Roles;
use App\Entity\Usuarios;
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
            $tokenUser = $jwtManager->parse($token); // Lanza excepción si el token no es válido
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        if ($tokenUser["roles"][0] == "administrador") {
            $videojuegos = $em->getRepository(Videojuegos::class)->findAll();
        } else {
            $videojuegos = $em->getRepository(Videojuegos::class)->findBy(["deleted" => false]);
        }
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

    #[Route('/editar/{id}', name: 'editar_videojuego', methods: ['PUT'])]
    public function editarVideojuego($id, Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $tokenUser = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $videojuego = $em->getRepository(Videojuegos::class)->find($id);
        if (!$videojuego) {
            return new JsonResponse(['error' => 'Videojuego no encontrado'], 404);
        }

        $videojuego->setPrecio($data['precio'] ?? $videojuego->getPrecio());
        $videojuego->setDeleted($data['deleted'] ?? $videojuego->isDeleted());
        $videojuego->setStock($data['stock'] ?? $videojuego->getStock());
        $videojuego->setModifiedBy($em->getRepository(Usuarios::class)->find($tokenUser["id"]));
        $videojuego->setModifiedAt(new \DateTime());
        $em->flush();
        return new JsonResponse(['success' => true]);
    }

    #[Route('/eliminar/{id}', name: 'eliminar_videojuego', methods: ['DELETE'])]
    public function eliminarVideojuego($id, Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $tokenUser = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $videojuego = $em->getRepository(Videojuegos::class)->find($id);
        if (!$videojuego) {
            return new JsonResponse(['error' => 'Videojuego no encontrado'], 404);
        }
        $videojuego->setDeleted(true);
        $videojuego->setModifiedAt(new \DateTime());
        $videojuego->setModifiedBy($em->getRepository(Usuarios::class)->find($tokenUser["id"]));
        $em->flush();
        return new JsonResponse(['message' => "Videojuego eliminado correctamente"]);
    }
    
    #[Route('/editarCarrito', name: 'editar_carrito', methods: ['POST'])]
    public function editarCarrito(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $videojuegoId = $data['videojuego_id'] ?? null;
        $cantidad = $data['cantidad'] ?? 1;

        if (!$token || !$videojuegoId) {
            return new JsonResponse(['error' => 'Faltan datos'], 400);
        }
        try {
            $userData = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $usuario = $em->getRepository(Usuarios::class)->find($userData['id'] ?? 0);
        $videojuego = $em->getRepository(Videojuegos::class)->find($videojuegoId);
        if (!$usuario || !$videojuego) {
            return new JsonResponse(['error' => 'Usuario o videojuego no encontrado'], 404);
        }
        // Aquí tu lógica para añadir/editar el carrito (puedes usar una entidad Carrito)
        // ...

        return new JsonResponse(['success' => true]);
    }
}