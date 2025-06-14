<?php

namespace App\Controller;

use App\Entity\Roles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\Entity\Usuarios;

#[Route('/api/usuarios', name: 'usuarios_')]
final class UsuariosController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        $usuario = $em->getRepository(Usuarios::class)->findOneBy(['email' => $email]);
        if (!$usuario || !password_verify($password, $usuario->getPassword())) {
            return new JsonResponse(['error' => 'Email y/o contraseña incorrectos']);
        }

        $payload = [
            'id' => $usuario->getId(),
            'apellido' => $usuario->getApellido(),
            'email' => $usuario->getEmail(),
            'telefono' => $usuario->getTelefono(),
            'direccion' => $usuario->getDireccion(),
        ];
        $token = $jwtManager->createFromPayload($usuario, $payload);

        return new JsonResponse(['token' => $token]);
    }

    #[Route('/registro', name: 'app_registro', methods: ['POST'])]
    public function registro(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $required = ['nombre', 'apellido', 'email', 'password', 'telefono', 'direccion'];
        foreach ($required as $field) {
            if (empty($data[$field]) || !isset($data[$field]) || trim($data[$field]) === '') {
                return new JsonResponse(['error' => "Falta el campo $field"]);
            }
        }

        // Comprobar si el email ya existe
        if ($em->getRepository(Usuarios::class)->findOneBy(['email' => $data['email']])) {
            return new JsonResponse(['error' => 'El email ya está registrado']);
        }

        $usuario = new Usuarios();
        // Asignar rol
        $rol = $em->getRepository(Roles::class)->findOneBy(['nombre' => 'cliente']);
        if (!$rol) {
            return new JsonResponse(['error' => 'Rol no válido']);
        }
        $usuario->setRol($rol);

        $usuario->setNombre($data['nombre']);
        $usuario->setApellido($data['apellido']);
        $usuario->setEmail($data['email']);
        $usuario->setTelefono($data['telefono']);
        $usuario->setDireccion($data['direccion']);
        $usuario->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $usuario->setDeleted(false);
        $usuario->setCreatedAt(new \DateTime());
        $usuario->setCreatedBy(null); // Asignar creador por defecto
        $usuario->setModifiedAt(new \DateTime());
        $usuario->setModifiedBy(null); // Asignar modificador por defecto

        $em->persist($usuario);
        $em->flush();

        $payload = [
            'id' => $usuario->getId(),
            'apellido' => $usuario->getApellido(),
            'email' => $usuario->getEmail(),
            'telefono' => $usuario->getTelefono(),
            'direccion' => $usuario->getDireccion(),
        ];
        $token = $jwtManager->createFromPayload($usuario, $payload);

        return new JsonResponse(['token' => $token]);
    }

    #[Route('/listarUsuarios', name: 'listar_usuarios', methods: ['POST'])]
    public function listarUsuarios(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $userData = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }

        $usuarios = $em->getRepository(Usuarios::class)->findAll();
        $result = [];
        foreach ($usuarios as $usuario) {
            $result[] = [
                'id' => $usuario->getId(),
                'nombre' => $usuario->getNombre(),
                'apellido' => $usuario->getApellido(),
                'email' => $usuario->getEmail(),
                'telefono' => $usuario->getTelefono(),
                'direccion' => $usuario->getDireccion(),
                'rol' => $usuario->getRol()->getNombre(),
                'createdAt' => $usuario->getCreatedAt()->format('d/m/Y H:i:s'),
                'modifiedAt' => $usuario->getModifiedAt() ? $usuario->getModifiedAt()->format('d/m/Y H:i:s') : null,
                'deleted' => $usuario->isDeleted(),
                'createdBy' => $usuario->getCreatedBy() ? $usuario->getCreatedBy()->getNombre() . " " . $usuario->getCreatedBy()->getApellido() : null,
                'modifiedBy' => $usuario->getModifiedBy() ? $usuario->getModifiedBy()->getNombre() . " " . $usuario->getModifiedBy()->getApellido() : null,
            ];
        }
        return new JsonResponse($result);
    }
}