<?php

namespace App\Controller;

use App\Entity\ReserverRendezVous;
use App\Form\RendezVousType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RendezVousController extends AbstractController
{
    #[Route('/rendez-vous', name: 'app_rendez_vous')]
    public function reserver(Request $request, EntityManagerInterface $em): Response
    {
        $rdv = new ReserverRendezVous();
        $form = $this->createForm(RendezVousType::class, $rdv);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($rdv);
            $em->flush();

            $this->addFlash('success', 'Votre demande de rendez-vous a été envoyée avec succès !');

            return $this->redirectToRoute('app_rendez_vous');
        }

        return $this->render('rendezvous/rendez_vous.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
