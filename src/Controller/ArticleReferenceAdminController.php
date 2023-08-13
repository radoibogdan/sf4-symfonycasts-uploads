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
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ArticleReferenceAdminController extends BaseController
{
    /**
     * @Route("/admin/article/{id}/references", name="admin_article_add_references", methods={"POST"})
     * @IsGranted("MANAGE", subject="article")
     */
    public function uploadArticleReference(Article $article, Request $request, UploadHelper $uploadHelper, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('reference');

        // add mymeTypesMessage for specific message error
        $violations = $validator->validate(
            $uploadedFile,
            [
                new NotBlank(["message" => "Choisir un fichier pour le téléchargement."]),
                new File([
                    'maxSize' => '5M', # for > 5M => change upload_max_filesize in php.ini + restart apache
                    'mimeTypes' => [
                        'application/pdf',
                        'application/vnd.ms-excel',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    ], # More examples => MimeTypeExtensionGuesser.php
                    'mimeTypesMessage' => 'Formats acceptés : pdf, image'
                ])
            ]
        );

        if ($violations->count() > 0) {
            /** @var ConstraintViolation $violation */
            foreach ($violations as $violation) {
                $this->addFlash('error', $violation->getMessage());
            }

            return $this->redirectToRoute('admin_article_edit', ['id' => $article->getId()]);
        }

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