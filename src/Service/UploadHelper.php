<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadHelper
{
    const ARTICLE_IMAGE = 'article_image';
    /**
     * Chemin téléchargement récupéré depuis services.yaml
     * @var string
     */
    private $uploadsPath;
    /**
     * @var RequestStackContext
     */
    private $requestStackContext;

    public function __construct(string $uploadsPath, RequestStackContext $requestStackContext)
    {
        $this->uploadsPath = $uploadsPath;
        $this->requestStackContext = $requestStackContext;
    }

    public function uploadArticleImage(UploadedFile $uploadedFile): string
    {
        $destination = $this->uploadsPath.'/'.self::ARTICLE_IMAGE;
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        // Urlizer, Turn name "To The Moon" in "to-the-moon"
        $newFilename = Urlizer::urlize($originalFilename).'-'.uniqid().'.'.$uploadedFile->guessExtension();

        // Move file
        $uploadedFile->move(
            $destination,
            $newFilename
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