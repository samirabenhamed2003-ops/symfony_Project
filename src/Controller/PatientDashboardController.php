<?php

namespace App\Controller;

use App\Repository\ReserverRendezVousRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;




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




    #[Route('/rendez-vous/{id}/annuler', name: 'patient_rdv_annuler', methods: ['POST'])]
public function annulerRendezVous(
    int $id,
    Request $request,
    ReserverRendezVousRepository $rdvRepo,
    EntityManagerInterface $em,
    MailerInterface $mailer
): Response {
    $rdv = $rdvRepo->find($id);

    // 🔐 Sécurité
    if (!$rdv || $rdv->getPatient() !== $this->getUser()) {
        throw $this->createAccessDeniedException();
    }

    // 🔐 CSRF
    if (!$this->isCsrfTokenValid('annuler_rdv_' . $rdv->getId(), $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Token invalide.');
    }

    // ❌ Annulation autorisée uniquement si accepté
    if ($rdv->getStatut() !== 'valide') {
        $this->addFlash('warning', 'Ce rendez-vous ne peut pas être annulé.');
        return $this->redirectToRoute('patient_rdv_detail', ['id' => $id]);
    }

    // ✅ Mise à jour
    $rdv->setStatut('annule_par_patient');
    $em->flush();

    // 📧 Email au médecin
    if ($rdv->getMedecin()) {
        $email = (new Email())
            ->from('no-reply@mycabinet.tn')
            ->to($rdv->getMedecin()->getEmail())
            ->subject('Annulation d’un rendez-vous')
            ->html(
                $this->renderView('emails/rdv_annule_patient.html.twig', [
                    'rdv' => $rdv
                ])
            );

        $mailer->send($email);
    }

    $this->addFlash('success', 'Votre rendez-vous a été annulé avec succès.');

    return $this->redirectToRoute('patient_dashboard');
}

}