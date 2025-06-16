<?php

namespace App\Controller;

use App\Entity\Compras;
use App\Entity\DetallesCompra;
use App\Entity\Usuarios;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/compras', name: 'usuarios_')]
final class ComprasController extends AbstractController
{
    #[Route('/historial', name: 'historial_compras', methods: ['POST'])]
    public function historialCompras(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $userToken = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $usuario = $em->getRepository(Usuarios::class)->find($userToken['id']);
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }
        $compras = $em->getRepository(Compras::class)->findBy(["usuario" => $usuario->getId()], ['fecha' => 'DESC']);
        $result = [];
        foreach ($compras as $compra) {
            $detalles = [];
            foreach ($compra->getDetallesCompras() as $detalle) {
                $detalles[] = [
                    'videojuego' => $detalle->getVideojuego()->getNombre(),
                    'plataforma' => $detalle->getVideojuego()->getPlataforma()->getNombre(),
                    'cantidad' => $detalle->getCantidad(),
                    'precio_unitario' => $detalle->getPrecioUnitario()
                ];
            }
            $result[] = [
                'fecha' => $compra->getFecha()->format('d/m/Y H:i:s'),
                'precio_total' => $compra->getPrecioTotal(),
                'detalles' => $detalles
            ];
        }
        return new JsonResponse($result);
    }
}
