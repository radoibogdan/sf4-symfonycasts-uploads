<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleReference;
use App\Service\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ArticleReferenceAdminController extends BaseController
{
    /**
     * @Route("/admin/article/{id}/references", name="admin_article_add_references", methods={"POST"})
     * @IsGranted("MANAGE", subject="article")
     */
    public function uploadArticleReference(Article $article, Request $request, UploadHelper $uploadHelper, EntityManagerInterface $entityManager)
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('reference');
        $filename = $uploadHelper->uploadArticleReference($uploadedFile);
        $articleReference = (new ArticleReference($article))
            ->setFilename($filename)
            ->setOriginalFilename($uploadedFile->getClientOriginalName() ?? $filename)
            ->setMimeType($uploadedFile->getMimeType() ?? 'application/octet-stream');

        $entityManager->persist($articleReference);
        $entityManager->flush();

        return $this->redirectToRoute('admin_article_edit', [
            'id' => $article->getId()
        ]);
    }
}