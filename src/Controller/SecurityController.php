<?php

namespace App\Controller;

use PDO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    // === RUTA 1: GENERADOR DE LA IMAGEN CAPTCHA ===
    #[Route('/captcha', name: 'app_captcha')]
    public function captcha(Request $request): Response
    {
        // 1. Generamos un número de 5 cifras
        $codigo = (string) rand(10000, 99999);
        // 2. Lo guardamos en la sesión para compararlo luego
        $request->getSession()->set('captcha_code', $codigo);

        // 3. Dibujamos la imagen (Librería GD nativa de PHP)
        $imagen = imagecreatetruecolor(120, 40);
        $fondo = imagecolorallocate($imagen, 34, 40, 49); // Color oscuro
        $texto = imagecolorallocate($imagen, 255, 190, 51); // Color amarillo/naranja
        
        imagefill($imagen, 0, 0, $fondo);
        imagestring($imagen, 5, 35, 12, $codigo, $texto);

        // 4. Devolvemos la imagen al navegador
        ob_start();
        imagepng($imagen);
        $imagenData = ob_get_clean();
        imagedestroy($imagen);

        return new Response($imagenData, 200, ['Content-Type' => 'image/png']);
    }

    // === RUTA 2: EL FORMULARIO DE REGISTRO ===
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, PDO $pdo): Response
    {
        $error = null;

        if ($request->isMethod('POST')) {
            $username = trim($request->request->get('username'));
            $email = trim($request->request->get('email'));
            $password = $request->request->get('password');
            $captchaInput = $request->request->get('captcha');
            $captchaReal = $request->getSession()->get('captcha_code');

            // Validaciones
            if (empty($username) || empty($email) || empty($password)) {
                $error = "Todos los campos son obligatorios.";
            } elseif ($captchaInput !== $captchaReal) {
                $error = "El código numérico de seguridad no coincide.";
            } else {
                // Comprobamos duplicados (Requisito Rúbrica)
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR username = ?");
                $stmt->execute([$email, $username]);
                
                if ($stmt->fetch()) {
                    $error = "El usuario o correo ya están en uso en el Garaje.";
                } else {
                    // Encriptamos contraseña
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Insertamos con PDO
                    $insert = $pdo->prepare("INSERT INTO usuarios (username, email, password) VALUES (?, ?, ?)");
                    $insert->execute([$username, $email, $hash]);

                    // Login automático
                    $request->getSession()->set('usuario_id', $pdo->lastInsertId());
                    $request->getSession()->set('usuario_nombre', $username);
                    $request->getSession()->set('usuario_rol', 'ROLE_USER');

                    // Redirigimos al inicio
                    return $this->redirectToRoute('app_circuito_index');
                }
            }
        }

        return $this->render('security/register.html.twig', [
            'error' => $error
        ]);
    }
    // === RUTA 3: EL LOGIN ===
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, PDO $pdo): Response
    {
        // Requisito Rúbrica: Si ya está logueado, no puede entrar aquí
        if ($request->getSession()->get('usuario_id')) {
            return $this->redirectToRoute('app_circuito_index');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email'));
            $password = $request->request->get('password');

            // Buscamos al usuario por su email
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Comprobamos si existe y si la contraseña bcrypt es correcta (Requisito Rúbrica)
            if ($user && password_verify($password, $user['password'])) {
                // Guardamos sus datos en la sesión
                $request->getSession()->set('usuario_id', $user['id']);
                $request->getSession()->set('usuario_nombre', $user['username']);
                $request->getSession()->set('usuario_rol', $user['rol']);

                return $this->redirectToRoute('app_circuito_index');
            } else {
                $error = "Correo o contraseña incorrectos.";
            }
        }

        return $this->render('security/login.html.twig', [
            'error' => $error
        ]);
    }

    // === RUTA 4: CERRAR SESIÓN ===
    #[Route('/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        // Destruimos la sesión entera
        $request->getSession()->invalidate();
        return $this->redirectToRoute('app_circuito_index');
    }
}