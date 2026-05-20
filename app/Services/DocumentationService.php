<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;

class DocumentationService
{
    private string $docsRoot;

    /** @var array<string, array{slug: string, path: string, title: string, description: ?string}>|null */
    private ?array $documentsBySlug = null;

    /** @var array<string, string>|null */
    private ?array $slugByPath = null;

    public function __construct(?string $docsRoot = null)
    {
        $this->docsRoot = $docsRoot ?? base_path('docs');
    }

    /**
     * @return list<array{title: string, documents: list<array{slug: string, title: string, description: ?string}>}>
     */
    public function index(): array
    {
        return collect(config('documentation.groups', []))
            ->map(function (array $group): array {
                return [
                    'title' => $group['title'],
                    'documents' => collect($group['documents'])
                        ->map(fn (array $document): array => [
                            'slug' => $document['slug'],
                            'title' => $document['title'],
                            'description' => $document['description'] ?? null,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{slug: string, title: string, description: ?string, content: string}
     */
    public function show(string $slug): array
    {
        $document = $this->documentsBySlug()[$slug] ?? null;

        if ($document === null) {
            abort(404);
        }

        $content = $this->readDocument($document['path']);
        $content = $this->rewriteInternalLinks($content, $document['path']);

        return [
            'slug' => $document['slug'],
            'title' => $document['title'],
            'description' => $document['description'] ?? null,
            'content' => $content,
        ];
    }

    private function readDocument(string $relativePath): string
    {
        $absolutePath = $this->resolvePath($relativePath);

        if (! is_file($absolutePath)) {
            abort(404, 'Документ не найден.');
        }

        $content = file_get_contents($absolutePath);

        if ($content === false) {
            throw new RuntimeException('Не удалось прочитать документ.');
        }

        return $content;
    }

    private function resolvePath(string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        $absolutePath = realpath($this->docsRoot.'/'.$relativePath);
        $docsRoot = realpath($this->docsRoot);

        if ($absolutePath === false || $docsRoot === false || ! Str::startsWith($absolutePath, $docsRoot.DIRECTORY_SEPARATOR)) {
            abort(404);
        }

        return $absolutePath;
    }

    private function rewriteInternalLinks(string $content, string $currentPath): string
    {
        $slugByPath = $this->slugByPath();

        return preg_replace_callback(
            '/\]\(([^)\s#]+)(#[^)]+)?\)/',
            function (array $matches) use ($slugByPath, $currentPath): string {
                $target = $matches[1];
                $anchor = $matches[2] ?? '';

                if (Str::startsWith($target, ['http://', 'https://', 'mailto:', '/docs/'])) {
                    return $matches[0];
                }

                $resolvedPath = $this->resolveRelativePath($currentPath, $target);

                if ($resolvedPath === null || ! isset($slugByPath[$resolvedPath])) {
                    return $matches[0];
                }

                return '](/docs/'.$slugByPath[$resolvedPath].$anchor.')';
            },
            $content,
        ) ?? $content;
    }

    private function resolveRelativePath(string $currentPath, string $link): ?string
    {
        $link = str_replace('\\', '/', $link);

        if (Str::startsWith($link, 'docs/')) {
            return $this->normalizeDocPath($link);
        }

        if (! Str::endsWith($link, '.md')) {
            return null;
        }

        $currentDir = str_replace('\\', '/', dirname($currentPath));
        if ($currentDir === '.') {
            $currentDir = '';
        }

        $base = $currentDir === '' ? '' : $currentDir.'/';
        $resolved = $this->normalizePath($base.$link);

        return $this->normalizeDocPath($resolved);
    }

    private function normalizePath(string $path): string
    {
        $parts = [];

        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                array_pop($parts);

                continue;
            }

            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    private function normalizeDocPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        if (Str::startsWith($path, 'docs/')) {
            $path = Str::after($path, 'docs/');
        }

        return $path;
    }

    /**
     * @return array<string, array{slug: string, path: string, title: string, description: ?string}>
     */
    private function documentsBySlug(): array
    {
        if ($this->documentsBySlug !== null) {
            return $this->documentsBySlug;
        }

        $this->documentsBySlug = [];

        foreach (config('documentation.groups', []) as $group) {
            foreach ($group['documents'] as $document) {
                $this->documentsBySlug[$document['slug']] = $document;
            }
        }

        return $this->documentsBySlug;
    }

    /**
     * @return array<string, string>
     */
    private function slugByPath(): array
    {
        if ($this->slugByPath !== null) {
            return $this->slugByPath;
        }

        $this->slugByPath = [];

        foreach ($this->documentsBySlug() as $document) {
            $this->slugByPath[$this->normalizeDocPath($document['path'])] = $document['slug'];
        }

        return $this->slugByPath;
    }
}
