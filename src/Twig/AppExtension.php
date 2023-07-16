<?php

namespace App\Twig;

use App\Service\MarkdownHelper;
use App\Service\UploadHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('cached_markdown', [$this, 'processMarkdown'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            # Renvoie le path public des images téléchargées
            new TwigFunction('uploaded_asset', [$this, 'getUploadedAssetPath'])
        ];
    }

    public function processMarkdown($value)
    {
        return $this->container
            ->get(MarkdownHelper::class)
            ->parse($value);
    }

    /**
     * Met a disposition dans ce fichier les services renvoyés dans l'array
     * @return string[]
     */
    public static function getSubscribedServices()
    {
        return [
            MarkdownHelper::class,
            UploadHelper::class,
        ];
    }

    /**
     * Récupère le path public des images téléchargées
     *
     * @param string $path
     * @return string
     */
    public function getUploadedAssetPath(string $path): string
    {
        return $this->container
            ->get(UploadHelper::class)
            ->getPublicPath($path);
    }
}
