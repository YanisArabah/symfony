<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Article;
use App\Form\ArticleType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $article = $entityManager->getRepository(Article::class)->findAll();

        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
            'article' => $article,
        ]);
    }

    #[Route('/article/creer', name: 'app_article_create')]
    public function creer(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, #[Autowire('%kernel.project_dir%/public/uploads/images')] string $imagesDirectory): Response
    {

        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $nomOriginal = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $nomSafe = $slugger->slug($nomOriginal);
                $nvNomFichier = $nomSafe.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move($imagesDirectory, $nvNomFichier);
                } catch (FileException $e) {
                    $this->addFlash("error", "Problème lors de la gestion de l'image");
                }

                $article->setImage($nomOriginal); // Image correspond au nom de l'image, et non pas son contenu
            }

            $entityManager->persist($article);
            $entityManager->flush();
            $this->addFlash('success', "L'article a bien été créé !");

            return $this->redirectToRoute('app_article_lister');
        }

        return $this->render('article/creer.html.twig', [
            'controller_name' => 'ArticleController',
            'titre' => 'Article',
            'form' => $form,
        ]);
    }

    #[Route('/article/modifier/{id}', name: 'app_article_modifier')]
    public function modifier(int $id, Request $request, EntityManagerInterface $entityManager): Response
{
        $article = $entityManager->getRepository(Article::class)->find($id);
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('app_article_lister');
        }

        return $this->render('article/creer.html.twig', [
            'controller_name' => 'ArticleController',
            'titre' => 'Article',
            'form' => $form,
        ]);
}

    #[Route('/article/supprimer/{id}', name: 'app_article_supprimer')]
    public function supprimer(int $id, EntityManagerInterface $entityManager): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException('No article found for id ' . $id);
        }

        $entityManager->remove($article);
        $entityManager->flush();

        return $this->redirectToRoute('app_article_lister');
    }

    #[Route('/article/lister', name: 'app_article_lister')]
    public function listing(EntityManagerInterface $entityManager): Response
    {
        $articles = $entityManager->getRepository(Article::class)->findAll();

        return $this->render('article/lister.html.twig', [
            'controller_name' => 'ArticleController',
            'articles' => $articles,
        ]);
    }
}
