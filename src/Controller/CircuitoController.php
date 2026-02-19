<?php

namespace App\Controller;

use PDO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CircuitoController extends AbstractController
{
    #[Route('/', name: 'app_circuito_index')]
    public function index(PDO $pdo): Response
    {
        // Prueba rápida de PDO con Consulta Preparada (Requisito Anexo I)
        $stmt = $pdo->prepare("SELECT * FROM categorias ORDER BY nombre ASC");
        $stmt->execute();
        $categorias = $stmt->fetchAll();

        return $this->render('circuito/index.html.twig', [
            'categorias' => $categorias
        ]);
    }
}