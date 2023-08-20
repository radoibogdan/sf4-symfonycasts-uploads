<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleReference;
use App\Service\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
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
                        'image/*',
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

    /**
     * @Route("/admin/article/references/{id}/download", name="admin_article_download_reference", methods={"GET"})
     */
    public function downloadArticleReference(ArticleReference $reference, UploadHelper $uploadHelper)
    {
        $article = $reference->getArticle();
        $this->denyAccessUnlessGranted('MANAGE', $article);

        // Show file in browser
        $response = new StreamedResponse(function () use($reference, $uploadHelper) {
            $fileStream = $uploadHelper->readStream($reference->getFilePath(), false);
            $outputStream = fopen('php://output', 'wb'); # wb = write + b for windows (binary)
            stream_copy_to_stream($fileStream, $outputStream);
        });
        $response->headers->set('Content-Type', $reference->getMimeType());

        // Download file directly
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $reference->getOriginalFilename()
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;

    }

    /**
     * @Route("api/admin/article/{id}/references", name="api_admin_article_add_references", methods={"POST"})
     * @IsGranted("MANAGE", subject="article")
     */
    public function uploadArticleReferenceApi(Article $article, Request $request, UploadHelper $uploadHelper, EntityManagerInterface $entityManager, ValidatorInterface $validator)
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
                        'image/*',
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
            return $this->json($violations, Response::HTTP_BAD_REQUEST);
        }

        $filename = $uploadHelper->uploadArticleReference($uploadedFile);
        $articleReference = (new ArticleReference($article))
            ->setFilename($filename)
            ->setOriginalFilename($uploadedFile->getClientOriginalName() ?? $filename)
            ->setMimeType($uploadedFile->getMimeType() ?? 'application/octet-stream');

        $entityManager->persist($articleReference);
        $entityManager->flush();

        # Create group main to avoid infinite loop serialization of $articleReference, see group "main" in ArticleReference
        return $this->json(
            $articleReference,
            Response::HTTP_CREATED,
            [],
            ['groups' => ['main']]
        );
    }

    /**
     * Used by dropzone + js to render dynamic article reference list in edit_v2_api.html.twig + admin_article_form.js
     *
     * @Route("api/admin/article/{id}/references", methods="GET", name="api_admin_article_list_references")
     * @IsGranted("MANAGE", subject="article")
     */
    public function getArticleReferences(Article $article)
    {
        return $this->json(
            $article->getArticleReferences(),
            Response::HTTP_OK,
            [],
            ['groups' => ['main']] # create group main to avoid infinite loop serialization of $articleReference, see group "main" in ArticleReference
        );
    }

    /**
     * @Route("admin/article/references/{id}", name="admin_article_delete_reference", methods={"DELETE"})
     */
    public function deleteArticleReference(ArticleReference $reference, UploadHelper $uploadHelper, EntityManagerInterface $entityManager)
    {
        $article = $reference->getArticle();
        $this->denyAccessUnlessGranted('MANAGE', $article);

        # TODO: Use doctrine transaction to commit transaction (remove $reference) after file was deleted
        $entityManager->remove($reference);
        $entityManager->flush();

        $uploadHelper->deleteFile($reference->getFilePath(), false);

        // All is good, I have nothing else to say
        return new Response(null, Response::HTTP_NO_CONTENT);
    }


    /**
     * @Route("admin/article/references/{id}", name="admin_article_update_reference", methods={"PUT"})
     */
    public function updateArticleReference(
        ArticleReference $reference,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Request $request,
        ValidatorInterface $validator
    ) {
        $article = $reference->getArticle();
        $this->denyAccessUnlessGranted('MANAGE', $article);

        $serializer->deserialize(
            $request->getContent(),
            ArticleReference::class,
            'json',
            [
                'object_to_populate' => $reference, # update existing $reference passed from arguments
                'groups' => ['input']               # restrict update only to originalFilename, see group "input" in ArticleReference
            ]
        );

        $violations = $validator->validate($reference);
        if ($violations->count() > 0) {
            # TODO: Errors are not handled in view, highlight input in red and print error below
            return $this->json($violations, Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($reference);
        $entityManager->flush();

        # Create group main to avoid infinite loop serialization of $reference, see group "main" in ArticleReference
        return $this->json(
            $reference,
            Response::HTTP_OK,
            [],
            ['groups' => ['main']]
        );
    }
}