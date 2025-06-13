<?php

namespace App\Controller;

use App\Entity\Roles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
}