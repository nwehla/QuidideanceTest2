<?php

namespace App\Controller;

use DateTime;
use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Form\UtilisateurModifierType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Guard\AuthenticatorInterface;

/**
 * @Route("/admin/utilisateur")
 */
class UtilisateurController extends AbstractController
{
    public function uniqidReal($lenght = 13) {
        // uniqid donne 13 caratères, mais pouvez ajuster si vous voulez.
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($lenght / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
        } else {
            throw new Exception("no cryptographically secure random function available");
        }
        return substr(bin2hex($bytes), 0, $lenght);
    }
    
    /**
     * @Route("/", name="app_utilisateur_index", methods={"GET"})
     */
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        if ($this->isGranted('ROLE_SUPERADMIN')) {
        return $this->render('utilisateur/utilisateur_index.html.twig', [
            'utilisateurs' => $utilisateurRepository->findAll(),
        ]);
        }
        else{
            return $this->redirectToRoute('app_accueil', [], Response::HTTP_SEE_OTHER);
        }
    }
    // creation d'une fonction d'activation du token
/**
 * @Route("/activation/{token}", name="activation")
 */
    public function gestionToken($token,UtilisateurRepository $repo,EntityManagerInterface $manager)
    {
       //on verifie si un utilisateur a ce token
       $utilisateur = $repo->findOneBy(['activation_token' => $token]);
       
       //si aucun utilisateur n'existe pas avec ce token.$_COOKIE
       if(!$utilisateur)
       {
           //Erreur 404
           throw $this->createNotFoundException('Cet utilisateur n\'existe pas');
       }
       
       //on supprime le token.
       $utilisateur->setActivateToken(null);
       //on persist
       $manager->persist($utilisateur);
       //on flush
       $manager->flush($utilisateur);
       //on envoie un message flash.
       $this->addflash('message','Vous avez bien activé votre compte');
       //on retourne à l'accueil.
       return $this->redirectToRoute('accueil');


    }


    /**
     * @Route("/new", name="app_utilisateur_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UtilisateurRepository $utilisateurRepository, UserPasswordHasherInterface $encoder,EntityManagerInterface $manager,
    \Swift_Mailer $mailer,GuardAuthenticatorHandler $guardHandler,AuthenticatorInterface $authenticator): Response
    {
        if ($this->isGranted('ROLE_SUPERADMIN')) {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $utilisateur = $form->getData();
         
            //Encodage du mot de passe
            $password = $encoder->hashPassword($utilisateur , $utilisateur->getPassword());
            
            $utilisateur->setPassword($password);
            //initialisation de la date de creation 
            $utilisateur->setDatecreation(new DateTime());
            //initialisation du token et cryptage de celui-ci
            $utilisateur->setActivateToken(md5($this->uniqidReal()));
            dd($utilisateur);
            
            //Persist
            $manager->persist($utilisateur);
            
            //Flush
            $manager->flush(); 
            //on créé le message d'activation de compte
            $message = (new \Swift_Message('Activation de votre compte'))
                ->setFrom('votreadresse@.fr')
            //on attribue le destinataire
                    ->setTo($utilisateur->getEmail())
            //on créé le contenu
                ->setBody(
                    $this->renderView('email/activation.html.twig' ,['token' => $utilisateur->getActivationToken()]
                ),
                'text/html'
                )
                ;
                //on envoie l'email
                $mailer->send($message);
                return $guardHandler->authenticateUserAndHandleSuccess(
                    $utilisateur,
                    $request,
                    $authenticator,
                    'main' //firewall nom dans security.yaml
                );
                

            $utilisateurRepository->add($utilisateur);
            $this->addFlash("success","La création a été effectuée");
            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form->createView(),
        ]);
        }
        else{
            return $this->redirectToRoute('app_accueil', [], Response::HTTP_SEE_OTHER);
        }
    }

    /**
     * @Route("/{id}", name="app_utilisateur_show", methods={"GET"})
     */
    public function show(Utilisateur $utilisateur): Response
    {
        if ($this->isGranted('ROLE_SUPERADMIN')) {
        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
        }
        else{
            return $this->redirectToRoute('app_accueil', [], Response::HTTP_SEE_OTHER);
        }
    }

    /**
     * @Route("/{id}/edit", name="app_utilisateur_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Utilisateur $utilisateur, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $manager): Response
    {
        if ($this->isGranted('ROLE_SUPERADMIN')) {
        $form = $this->createForm(UtilisateurModifierType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //initialisation de la date de creation 
            $utilisateur->setDatemiseajour(new DateTime());
            
            //Persist
            $manager->persist($utilisateur);
             
            //Flush
            $manager->flush();
            $utilisateurRepository->add($utilisateur);
            $this->addFlash("success","La modification a été effectuée");
            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form->createView(),
        ]);
        }
        else{
            return $this->redirectToRoute('app_accueil', [], Response::HTTP_SEE_OTHER);
        }
    }

    /**
     * @Route("/{id}", name="app_utilisateur_delete", methods={"POST"})
     */
    public function delete(Request $request, Utilisateur $utilisateur, UtilisateurRepository $utilisateurRepository): Response
    {
        if ($this->isGranted('ROLE_SUPERADMIN')) {
        if ($this->isCsrfTokenValid('delete'.$utilisateur->getId(), $request->request->get('_token'))) {
            $utilisateurRepository->remove($utilisateur);
            $this->addFlash("success","La suppression a été effectuée");
        }

        return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
    }
    else{
        return $this->redirectToRoute('app_accueil', [], Response::HTTP_SEE_OTHER);
    }
    }
}
