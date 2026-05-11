<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class ArticlesController extends AbstractController
{
    #[Route('/articles', name: 'app_articles')]
    public function index(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();

        return $this->render('articles/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/articles/nouveau', name: 'app_article_nouveau')]
    #[IsGranted('ROLE_USER')]
    public function nouveau(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $article = new Article();
        $article->setAuteurUser($this->getUser());
        
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($article);
            $em->flush();

            // Send notification email
            $email = (new Email())
                ->from('noreply@tp1symfony.com')
                ->to('lndlsmhmd@gmail.com')
                ->subject('Nouvel article publié : ' . $article->getTitre())
                ->html('
                    <h1>Nouvel article créé !</h1>
                    <p><strong>Titre :</strong> ' . $article->getTitre() . '</p>
                    <p><strong>Auteur :</strong> ' . $article->getAuteur() . '</p>
                    <p><strong>Date :</strong> ' . $article->getDateCreation()->format('d/m/Y') . '</p>
                ');

            $mailer->send($email);
            
            $this->addFlash('success', 'Article créé et notification envoyée !');
            return $this->redirectToRoute('app_articles');
        }
        
        return $this->render('articles/nouveau.html.twig', [
            'formulaire' => $form,
        ]);
    }

    #[Route('/articles/{id}', name: 'app_article_detail', requirements: ['id' => '\d+'])]
    public function detail(Article $article): Response
    {
        return $this->render('articles/detail.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/articles/{id}/modifier', name: 'app_article_modifier', requirements: ['id' => '\d+'])]
    public function modifier(Article $article, Request $request, EntityManagerInterface $em): Response
    {
        if ($article->getAuteurUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'auteur de cet article !');
        }
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush(); // Pas besoin de persist() car l'entité est déjà gérée par Doctrine

            $this->addFlash('success', 'Article modifié avec succès !');
            return $this->redirectToRoute('app_article_detail', ['id' => $article->getId()]);
        }

        return $this->render('articles/modifier.html.twig', [
            'formulaire' => $form,
            'article' => $article,
        ]);
    }

    #[Route('/articles/{id}/supprimer', name: 'app_article_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimer(Article $article, Request $request, EntityManagerInterface $em): Response
    {
        // Vérification du token CSRF pour la sécurité
        if ($this->isCsrfTokenValid('supprimer_' . $article->getId(), $request->request->get('_token'))) {
            $em->remove($article);
            $em->flush();

            $this->addFlash('success', 'Article supprimé avec succès.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide. Suppression annulée.');
        }

        return $this->redirectToRoute('app_articles');
    }
}