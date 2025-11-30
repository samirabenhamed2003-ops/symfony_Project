<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home_public')]
    public function publicHome(): Response
    {
        return $this->render('home/public_home.html.twig');
    }

    #[Route('/home', name: 'app_home')]
    public function userHome(): Response
    {
        return $this->render('home/index.html.twig');
    }
}
