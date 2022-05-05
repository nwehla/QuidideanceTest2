<?php

namespace App\Controller;

use App\Entity\Interroger;
use App\Form\InterrogerType;
use App\Repository\InterrogerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/interroger")
 */
class InterrogerController extends AbstractController
{
    /**
     * @Route("/", name="app_interroger_index", methods={"GET"})
     */
    public function index(InterrogerRepository $interrogerRepository): Response
    {
        return $this->render('interroger/interroger_index.html.twig', [
            'interrogers' => $interrogerRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="app_interroger_new", methods={"GET", "POST"})
     */
    public function new(Request $request, InterrogerRepository $interrogerRepository): Response
    {
        $interroger = new Interroger();
        $form = $this->createForm(InterrogerType::class, $interroger);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $interrogerRepository->add($interroger);
            $this->addFlash("success","La création a été effectuée");
            return $this->redirectToRoute('app_interroger_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('interroger/new.html.twig', [
            'interroger' => $interroger,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="app_interroger_show", methods={"GET"})
     */
    public function show(Interroger $interroger): Response
    {
        return $this->render('interroger/show.html.twig', [
            'interroger' => $interroger,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_interroger_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Interroger $interroger, InterrogerRepository $interrogerRepository): Response
    {
        $form = $this->createForm(InterrogerType::class, $interroger);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $interrogerRepository->add($interroger);
            $this->addFlash("success","La modification a été effectuée");
            return $this->redirectToRoute('app_interroger_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('interroger/edit.html.twig', [
            'interroger' => $interroger,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="app_interroger_delete", methods={"POST"})
     */
    public function delete(Request $request, Interroger $interroger, InterrogerRepository $interrogerRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$interroger->getId(), $request->request->get('_token'))) {
            $interrogerRepository->remove($interroger);
            $this->addFlash("success","La suppression a été effectuée");
        }

        return $this->redirectToRoute('app_interroger_index', [], Response::HTTP_SEE_OTHER);
    }
}