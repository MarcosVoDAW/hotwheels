<?php

namespace App\Controller;

use PDO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CircuitoController extends AbstractController
{
    #[Route('/', name: 'app_circuito_index')]
    public function index(PDO $pdo, Request $request): Response
    {
        // 1. OBLIGAMOS a la sesión a despertar para que Twig pueda leer tu usuario
        if ($request->hasSession()) {
            $request->getSession()->start();
        }

        // 2. Cargamos las categorías
        $stmt = $pdo->prepare("SELECT * FROM categorias ORDER BY nombre ASC");
        $stmt->execute();
        $categorias = $stmt->fetchAll();

        return $this->render('circuito/index.html.twig', [
            'categorias' => $categorias
        ]);
    }
}