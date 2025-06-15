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
        // Lógica para añadir/editar el carrito
        $carrito = $usuario->getCarrito() ?? [];
        $stockDisponible = $videojuego->getStock();
        $encontrado = false;
        foreach ($carrito as &$item) {
            if ($item['videojuego_id'] == $videojuegoId) {
                if ($item['cantidad'] + $cantidad > $stockDisponible) {
                    return new JsonResponse(['error' => 'No hay suficiente stock disponible'], 400);
                }
                $item['cantidad'] += $cantidad;
                $encontrado = true;
                break;
            }
        }
        unset($item);
        if (!$encontrado) {
            if ($cantidad > $stockDisponible) {
                return new JsonResponse(['error' => 'No hay suficiente stock disponible'], 400);
            }
            $carrito[] = [
                'videojuego_id' => $videojuegoId,
                'cantidad' => $cantidad
            ];
        }
        // Guardar el array actualizado, no sobreescribirlo
        $usuario->setCarrito($carrito);
        $em->persist($usuario);
        $em->flush();
        return new JsonResponse(['success' => true]);
    }

    #[Route('/plataformas', name: 'obtener_plataformas', methods: ['POST'])]
    public function obtenerPlataformas(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $plataformas = $em->getConnection()->executeQuery('SELECT DISTINCT nombre FROM plataformas')->fetchAllAssociative();
        $nombres = array_map(fn($p) => $p['nombre'], $plataformas);
        return new JsonResponse($nombres);
    }

    #[Route('/categorias', name: 'obtener_categorias', methods: ['POST'])]
    public function obtenerCategorias(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $categorias = $em->getConnection()->executeQuery('SELECT DISTINCT nombre FROM categorias')->fetchAllAssociative();
        $nombres = array_map(fn($c) => $c['nombre'], $categorias);
        return new JsonResponse($nombres);
    }

    #[Route('/buscarPorTitulo', name: 'buscar_por_titulo', methods: ['POST'])]
    public function buscarPorTitulo(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $titulo = $data['titulo'] ?? '';
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $qb = $em->getRepository(Videojuegos::class)->createQueryBuilder('v');
        $qb->where('LOWER(v.nombre) LIKE :titulo')
            ->setParameter('titulo', '%' . strtolower($titulo) . '%');
        $videojuegos = $qb->getQuery()->getResult();
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

    #[Route('/resenas/listar', name: 'listar_resenas', methods: ['POST'])]
    public function listarResenas(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $productoId = $data['productoId'] ?? null;
        if (!$token || !$productoId) {
            return new JsonResponse(['error' => 'Faltan datos'], 400);
        }
        try {
            $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $videojuego = $em->getRepository(Videojuegos::class)->find($productoId);
        if (!$videojuego) {
            return new JsonResponse(['error' => 'Videojuego no encontrado'], 404);
        }
        $resenas = $videojuego->getResenas(); // Asumiendo relación getResenas()
        $result = [];
        foreach ($resenas as $resena) {
            $result[] = [
                'id' => $resena->getId(),
                'usuario' => $resena->getUsuario()->getNombre(),
                'comentario' => $resena->getComentario(),
                'puntuacion' => $resena->getPuntuacion()
            ];
        }
        return new JsonResponse($result);
    }

    #[Route('/resenas/crear', name: 'crear_resena', methods: ['POST'])]
    public function crearResena(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $productoId = $data['productoId'] ?? null;
        $comentario = $data['comentario'] ?? '';
        $puntuacion = $data['puntuacion'] ?? null;
        if (!$token || !$productoId || !$comentario || !$puntuacion) {
            return new JsonResponse(['error' => 'Faltan datos'], 400);
        }
        try {
            $userData = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $usuario = $em->getRepository(Usuarios::class)->find($userData['id'] ?? 0);
        $videojuego = $em->getRepository(Videojuegos::class)->find($productoId);
        if (!$usuario || !$videojuego) {
            return new JsonResponse(['error' => 'Usuario o videojuego no encontrado'], 404);
        }
        $resena = new \App\Entity\Resenas();
        $resena->setUsuario($usuario);
        $resena->setVideojuego($videojuego);
        $resena->setComentario($comentario);
        $resena->setPuntuacion($puntuacion);
        $resena->setFecha(new \DateTime());
        $em->persist($resena);
        $em->flush();
        return new JsonResponse(['success' => true]);
    }

    #[Route('/mejorValorados', name: 'mejor_valorados', methods: ['POST'])]
    public function mejorValorados(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $conn = $em->getConnection();
        $sql = "SELECT v.id, v.nombre, v.precio, v.deleted, v.fecha_lanzamiento, p.nombre as plataforma, 
                    (SELECT url FROM imagenes i WHERE i.videojuego_id = v.id LIMIT 1) as imagen,
                    ROUND(AVG(r.puntuacion),2) as valoracion_media
                FROM videojuegos v
                LEFT JOIN plataformas p ON v.plataforma_id = p.id
                LEFT JOIN resenas r ON r.videojuego_id = v.id
                WHERE v.deleted = 0
                GROUP BY v.id
                ORDER BY valoracion_media DESC NULLS LAST, v.nombre ASC
                LIMIT 5";
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery()->fetchAllAssociative();
        return new JsonResponse($result);
    }
}