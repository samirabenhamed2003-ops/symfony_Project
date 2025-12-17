<?php

namespace App\Controller;

use App\Entity\ReserverRendezVous;
use App\Entity\User;
use App\Repository\ReserverRendezVousRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/medecin')]
#[IsGranted('ROLE_DOCTOR')]
class MedecinDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'medecin_dashboard')]
    public function dashboard(ReserverRendezVousRepository $rdvRepo): Response
    {
        $medecin = $this->getUser();
        
        // Récupérer les rendez-vous de la spécialité du médecin connecté
        // ou les rendez-vous qui lui sont assignés
        $rendezvous = $rdvRepo->createQueryBuilder('r')
            ->where('r.medecin = :medecin OR r.specialite = :specialite')
            ->setParameter('medecin', $medecin)
            ->setParameter('specialite', $medecin->getSpecialite())
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('medecin/dashboard.html.twig', [
            'rendezvous' => $rendezvous,
        ]);
    }

    #[Route('/rendez-vous/{id}/details', name: 'medecin_rdv_details')]
    public function voirDetails(ReserverRendezVous $rdv): Response
    {
        return $this->render('medecin/rdv_details.html.twig', [
            'rdv' => $rdv,
        ]);
    }

    #[Route('/rendez-vous/{id}/accepter', name: 'medecin_rdv_accepter', methods: ['POST'])]
    public function accepterRendezVous(
        ReserverRendezVous $rdv,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer
    ): Response {
        // Vérifier que le rendez-vous est en attente
        if ($rdv->getStatut() !== 'en_attente') {
            $this->addFlash('warning', 'Ce rendez-vous a déjà été traité.');
            return $this->redirectToRoute('medecin_dashboard');
        }

        // 1️⃣ Vérifier si un compte patient existe déjà avec cet email
        $patient = $userRepo->findOneBy(['email' => $rdv->getEmail()]);

        $motDePasseGenere = null;

        // 2️⃣ Si le patient n'existe pas, créer un nouveau compte
        if (!$patient) {
            $patient = new User();
            $patient->setEmail($rdv->getEmail());
            $patient->setRoles(['ROLE_PATIENT']);

            // Générer un mot de passe aléatoire
            $motDePasseGenere = bin2hex(random_bytes(6)); // 12 caractères
            $hashedPassword = $passwordHasher->hashPassword($patient, $motDePasseGenere);
            $patient->setPassword($hashedPassword);

            $em->persist($patient);
        }

        // 3️⃣ Lier le rendez-vous au patient
        $rdv->setPatient($patient);
        $rdv->setStatut('valide');

        $em->flush();

        // 4️⃣ Envoyer l'email au patient
        try {
            $email = (new TemplatedEmail())
                ->from('maawianwe@gmail.com')
                ->to($rdv->getEmail())
                ->subject('Votre rendez-vous a été accepté ✅')
                ->htmlTemplate('emails/rdv_accepte.html.twig')
                ->context([
                    'rdv' => $rdv,
                    'motDePasse' => $motDePasseGenere, // Le mot de passe généré (ou null si patient existant)
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Rendez-vous accepté et email envoyé au patient.');
        } catch (\Exception $e) {
            // Logger l'erreur pour debug
            error_log('Erreur envoi email: ' . $e->getMessage());
            $this->addFlash('warning', 'Rendez-vous accepté mais erreur d\'envoi d\'email : ' . $e->getMessage());
        }

        return $this->redirectToRoute('medecin_dashboard');
    }

    #[Route('/rendez-vous/{id}/refuser', name: 'medecin_rdv_refuser', methods: ['POST'])]
    public function refuserRendezVous(
        ReserverRendezVous $rdv,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        // Vérifier que le rendez-vous est en attente
        if ($rdv->getStatut() !== 'en_attente') {
            $this->addFlash('warning', 'Ce rendez-vous a déjà été traité.');
            return $this->redirectToRoute('medecin_dashboard');
        }

        // Changer le statut en refusé
        $rdv->setStatut('refuse');
        $em->flush();

        // Envoyer un email au patient
        try {
            $email = (new TemplatedEmail())
                ->from('maawianwe@gmail.com')
                ->to($rdv->getEmail())
                ->subject('Votre demande de rendez-vous')
                ->htmlTemplate('emails/rdv_refuse.html.twig')
                ->context([
                    'rdv' => $rdv,
                ]);

            $mailer->send($email);

            $this->addFlash('info', 'Rendez-vous refusé et email envoyé au patient.');
        } catch (\Exception $e) {
            // Logger l'erreur pour debug
            error_log('Erreur envoi email refus: ' . $e->getMessage());
            $this->addFlash('warning', 'Rendez-vous refusé mais erreur d\'envoi d\'email : ' . $e->getMessage());
        }

        return $this->redirectToRoute('medecin_dashboard');
    }
}