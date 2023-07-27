<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadHelper
{
    const ARTICLE_IMAGE = 'article_image';
    /**
     * Chemin téléchargement récupéré depuis services.yaml
     * @var string
     */
    private $publicUploadFilesystem;
    /**
     * @var RequestStackContext
     */
    private $requestStackContext;

    public function __construct(FilesystemInterface $publicUploadFilesystem, RequestStackContext $requestStackContext)
    {
        $this->requestStackContext = $requestStackContext;
        $this->publicUploadFilesystem = $publicUploadFilesystem;
    }

    public function uploadArticleImage(File $file): string
    {
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }

        // Urlizer, Turn name "To The Moon" in "to-the-moon"
        $newFilename = Urlizer::urlize(pathinfo($originalFilename,PATHINFO_FILENAME)).'-'.uniqid().'.'.$file->guessExtension();

        // Move file
        $this->publicUploadFilesystem->write(
            self::ARTICLE_IMAGE.'/'.$newFilename,
            file_get_contents($file->getPathname())
        );

        return $newFilename;
    }

    /**
     * Appelé par la fonction twig créée dans AppExtension
     *
     * @param $path
     * @return string
     */
    public function getPublicPath($path): string
    {
        # getBasePath returns '' if project lives at root,
        # getBasePath returns the subdomain if project lives in a subdirectory
        # requestStackContext must be declared in services.yaml, it is not by default available as a dependency injection
        return $this->requestStackContext
                ->getBasePath().'/uploads/'.$path;
    }
}