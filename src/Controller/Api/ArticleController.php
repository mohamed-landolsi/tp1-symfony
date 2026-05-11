<?php
namespace App\Controller\Api;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/articles', name: 'api_articles_')]
class ArticleController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(ArticleRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $articles = $repository->findAll();
        $json = $serializer->serialize($articles, 'json', ['groups' => 'article:read']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Article $article, SerializerInterface $serializer): JsonResponse
    {
        $json = $serializer->serialize($article, 'json', ['groups' => 'article:read']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        CategorieRepository $categorieRepository
    ): JsonResponse {
        $article = $serializer->deserialize($request->getContent(), Article::class, 'json');
        $article->setDateCreation(new \DateTime());
        if ($article->isPublie() === null) {
            $article->setPublie(false);
        }
        $data = json_decode($request->getContent(), true);
        if (isset($data['categorie_id'])) {
            $categorie = $categorieRepository->find($data['categorie_id']);
            if (!$categorie) {
                return $this->json(['error' => 'Catégorie non trouvée'], Response::HTTP_BAD_REQUEST);
            }
            $article->setCategorie($categorie);
        }
        $errors = $validator->validate($article);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorsArray], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $em->persist($article);
        $em->flush();
        return $this->json($article, Response::HTTP_CREATED, [], ['groups' => 'article:read']);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        Article $article,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        CategorieRepository $categorieRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $this->json(['error' => 'JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }
        if (isset($data['titre'])) $article->setTitre($data['titre']);
        if (isset($data['contenu'])) $article->setContenu($data['contenu']);
        if (isset($data['auteur'])) $article->setAuteur($data['auteur']);
        if (array_key_exists('publie', $data)) $article->setPublie((bool) $data['publie']);
        if (array_key_exists('categorie_id', $data)) {
            if ($data['categorie_id'] === null) {
                $article->setCategorie(null);
            } else {
                $categorie = $categorieRepository->find($data['categorie_id']);
                if (!$categorie) {
                    return $this->json(['error' => 'Catégorie non trouvée'], Response::HTTP_BAD_REQUEST);
                }
                $article->setCategorie($categorie);
            }
        }
        $errors = $validator->validate($article);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorsArray], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $em->flush();
        return $this->json($article, Response::HTTP_OK, [], ['groups' => 'article:read']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Article $article, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($article);
        $em->flush();
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}