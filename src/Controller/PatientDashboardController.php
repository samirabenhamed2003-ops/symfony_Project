<?php

namespace App\Controller;

use App\Repository\ReserverRendezVousRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patient')]
#[IsGranted('ROLE_PATIENT')]
class PatientDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'patient_dashboard')]
    public function dashboard(ReserverRendezVousRepository $rdvRepo): Response
    {
        // Récupérer les rendez-vous du patient connecté
        $patient = $this->getUser();
        
        // Récupérer les rendez-vous du patient connecté, triés par date et heure
        $mesRendezVous = $rdvRepo->createQueryBuilder('r')
            ->where('r.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('r.date_rdv', 'ASC')
            ->addOrderBy('r.heure_rdv', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('patient/dashboard.html.twig', [
            'mes_rendez_vous' => $mesRendezVous,
        ]);
    }

    #[Route('/rendez-vous/{id}', name: 'patient_rdv_detail')]
    public function detailRendezVous(ReserverRendezVousRepository $rdvRepo, int $id): Response
    {
        $rdv = $rdvRepo->find($id);

        // Vérifier que le rendez-vous appartient bien au patient connecté
        if (!$rdv || $rdv->getPatient() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à ce rendez-vous.');
        }

        return $this->render('patient/rdv_detail.html.twig', [
            'rdv' => $rdv,
        ]);
    }
}