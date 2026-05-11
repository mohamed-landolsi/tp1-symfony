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
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ArticleRepository;


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
    public function nouveau(Request $request, EntityManagerInterface $em): Response
    {
        $article = new Article();
        
        // Création du formulaire
        $form = $this->createForm(ArticleType::class, $article);
        
        // Traitement de la requête
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($article);
            $em->flush();
            
            // Message flash de confirmation
            $this->addFlash('success', 'Article créé avec succès !');
            
            return $this->redirectToRoute('app_articles');
        }
        
        return $this->render('articles/nouveau.html.twig', [
            'formulaire' => $form,
    #[Route('/articles/nouveau', name: 'app_article_nouveau')]
    public function nouveau(EntityManagerInterface $em): Response
    {
        $article = new Article();
        $article->setTitre('Mon premier article');
        $article->setContenu('Ceci est le contenu de mon premier article créé avec Doctrine.');
        $article->setAuteur('Étudiant');
        $article->setDateCreation(new \DateTime());
        $article->setPublie(true);

        $em->persist($article);
        $em->flush();

        return new Response("Article créé avec l'id : " . $article->getId());
    }

    #[Route('/articles/{id}', name: 'app_article_detail', requirements: ['id' => '\d+'])]
    public function detail(Article $article): Response
    {
        return $this->render('articles/detail.html.twig', [
            'article' => $article,
        ]);
    }
}
