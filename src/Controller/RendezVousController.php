<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\ReserverRendezVous;
use App\Form\RendezVousType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;


class RendezVousController extends AbstractController
{
    #[Route('/rendez-vous', name: 'app_rendez_vous')]
    public function reserver(Request $request, EntityManagerInterface $em, UserRepository $userRepo, MailerInterface $mailer ): Response
    {
        $rdv = new ReserverRendezVous();
        $form = $this->createForm(RendezVousType::class, $rdv);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Assigner un médecin selon la spécialité choisie
            $specialite = $rdv->getSpecialite();
            if ($specialite) {
                // Chercher un médecin avec cette spécialité et le rôle ROLE_DOCTOR
                $medecin = $userRepo->createQueryBuilder('u')
                    ->where('u.specialite = :specialite')
                    ->andWhere("u.roles LIKE :role")
                    ->setParameter('specialite', $specialite)
                    ->setParameter('role', '%ROLE_DOCTOR%')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

               if ($medecin) {
                    // Lier le rendez-vous au médecin
                    $rdv->setMedecin($medecin);
                } else {
                    // Aucun médecin trouvé pour cette spécialité
                    $this->addFlash('warning', 'Aucun médecin disponible pour cette spécialité pour le moment.');
                    return $this->redirectToRoute('app_rendez_vous');
                }
            }

            // Le statut est déjà défini à 'en_attente' dans le constructeur
            $em->persist($rdv);
            $em->flush();

             // === ENVOI DU MAIL ===
             $motDePasse = $motDePasse ?? null; // si tu l’as généré, sinon null
            $email = (new TemplatedEmail())
                ->from('maawianwe@gmail.com')                  // ton email
                ->to($rdv->getEmail())                         // email du patient
                ->subject('Confirmation de rendez-vous')
                ->htmlTemplate('emails/rdv_accepte.html.twig')
                ->context([
                    'rdv' => $rdv,
                    // 'motDePasse' => $motDePasse,            // si tu crées un mot de passe automatique
                ]);

            $mailer->send($email);
            // ====================

            $this->addFlash('success', 'Votre demande de rendez-vous a été envoyée avec succès ! Elle sera traitée par notre équipe médicale.');

            return $this->redirectToRoute('app_rendez_vous');
        }

        return $this->render('rendezvous/rendez_vous.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
