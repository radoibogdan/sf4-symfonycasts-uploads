<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadHelper
{
    const ARTICLE_IMAGE = 'article_image';
    const ARTICLE_REFERENCE = 'article_reference';
    /**
     * Chemin téléchargement récupéré depuis services.yaml
     * @var string
     */
    private $publicUploadFilesystem;

    private $requestStackContext;

    private $logger;

    private $publicAssetBaseUrl;
    /**
     * @var FilesystemInterface
     */
    private $privateUploadFilesystem;

    /**
     * @param FilesystemInterface $publicUploadFilesystem This is FLYSYSTEM
     * @param RequestStackContext $requestStackContext
     * @param LoggerInterface $logger
     */
    public function __construct(
        FilesystemInterface $publicUploadFilesystem, # if you're using v4 of oneup (flysystem v2) autowire Filesystem instead of FileSystemInterface
        FilesystemInterface $privateUploadFilesystem,
        RequestStackContext $requestStackContext,
        LoggerInterface     $logger,
                            $uploadedAssetBaseUrl # services.yaml
    ) {
        $this->requestStackContext     = $requestStackContext;
        $this->publicUploadFilesystem  = $publicUploadFilesystem;
        $this->logger                  = $logger;
        $this->publicAssetBaseUrl      = $uploadedAssetBaseUrl; # services.yaml
        $this->privateUploadFilesystem = $privateUploadFilesystem;
    }

    public function uploadArticleImage(File $file, ?string $existingFilename): string
    {
        $newFilename = $this->uploadFile($file, self::ARTICLE_IMAGE, true);
        # ---------------------------------------------------------------------------

        # Delete previous uploaded file if it exists
        if ($existingFilename) {
            # Log for admins, don't let users see Exception
            try {
                $result = $this->publicUploadFilesystem->delete(self::ARTICLE_IMAGE.'/'.$existingFilename);
                if ($result === false) {
                    throw new \Exception(sprintf('Could not delete the file "%s"', $existingFilename));
                }
            } catch (FileNotFoundException $exception) { # FlySystem FileNotFoundException
                $this->logger->alert(sprintf('Old uploaded file %s was missing when trying to delete it.', $existingFilename ));
            }
        }

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
                ->getBasePath().$this->publicAssetBaseUrl.'/'.$path;
    }

    public function uploadArticleReference(File $file): string
    {
        return $this->uploadFile($file, self::ARTICLE_REFERENCE, false);
    }

    public function uploadFile(File $file, string $destination, bool $isPublic): string
    {
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }

        # Urlizer, Turn name "To The Moon" in "to-the-moon"
        $newFilename = Urlizer::urlize(pathinfo($originalFilename,PATHINFO_FILENAME)).'-'.uniqid().'.'.$file->guessExtension();

        # --------------- Use FlySystem to move file as a stream -----------------
        $filesystem = $isPublic ? $this->publicUploadFilesystem : $this->privateUploadFilesystem;
        $stream = fopen($file->getPathname(), 'r'); # r = read
        $result = $filesystem->writeStream(
            $destination.'/'.$newFilename,
            $stream
        );

        if ($result === false) {
            throw new \Exception(sprintf('Could not upload the file "%s"', $newFilename));
        }

        # fclose may have been executed at this point
        # because some flysystem adapters close the stream by themselves
        if (is_resource($stream)) {
            fclose($stream);
        }

        return $newFilename;
    }
}