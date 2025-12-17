<?php

namespace App\Controller;
//namespace App\Entity;
use App\Entity\ReserverRendezVous;


use Doctrine\ORM\EntityManagerInterface;  // Assure-toi que cette ligne est présente

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
  public function userHome(EntityManagerInterface $em): Response
{
    $user = $this->getUser(); // Médecin connecté
    $rendezvous = $em->getRepository(ReserverRendezVous::class)->findBy([
        'medecin' => $user
    ]);

    return $this->render('medecin/dashboard.html.twig', [
        'rendezvous' => $rendezvous // Passe la variable 'rendezvous' ici
    ]);
}


    #[Route('/qui-sommes-nous', name: 'about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }
}
