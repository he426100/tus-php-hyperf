<?php

declare(strict_types=1);
/**
 * This file is part of he426100/tus-php-hyperf.
 *
 * @link     https://github.com/he426100/tus-php-hyperf
 * @contact  mrpzx001@gmail.com
 * @license  https://github.com/he426100/tus-php-hyperf/blob/master/LICENSE
 */
namespace Tus;

use Hyperf\HttpServer\Request as HyperfRequest;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Tus\Tus\Server;

class Request
{
    /**
     * @var HyperfRequest
     */
    protected $request;

    public function __construct(HyperfRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Get http method from current request.
     */
    public function method(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Get the current path info for the request.
     */
    public function path(): string
    {
        return $this->request->getPathInfo();
    }

    /**
     * Get upload key from url.
     */
    public function key(): string
    {
        return basename($this->path());
    }

    /**
     * Supported http requests.
     */
    public function allowedHttpVerbs(): array
    {
        return [
            HttpRequest::METHOD_GET,
            HttpRequest::METHOD_POST,
            HttpRequest::METHOD_PATCH,
            HttpRequest::METHOD_DELETE,
            HttpRequest::METHOD_HEAD,
            HttpRequest::METHOD_OPTIONS,
        ];
    }

    /**
     * Retrieve a header from the request.
     *
     * @param null|string|string[] $default
     */
    public function header(string $key, $default = null): ?string
    {
        if (! $this->request->hasHeader($key)) {
            return $default;
        }
        return $this->request->getHeaderLine($key);
    }

    /**
     * Get the root URL for the request.
     */
    public function url(): string
    {
        return $this->request->url();
    }

    /**
     * Extract metadata from header.
     */
    public function extractFromHeader(string $key, string $value): array
    {
        $meta = $this->header($key);

        if (strpos($meta, $value) !== false) {
            $meta = trim(str_replace($value, '', $meta));

            return explode(' ', $meta) ?? [];
        }

        return [];
    }

    /**
     * Extract base64 encoded filename from header.
     */
    public function extractFileName(): string
    {
        $name = $this->extractMeta('name') ?: $this->extractMeta('filename');

        if (! $this->isValidFilename($name)) {
            return '';
        }

        return $name;
    }

    /**
     * Extracts the metadata from the request header.
     */
    public function extractMeta(string $requestedKey): string
    {
        $uploadMetaData = $this->request->getHeaderLine('Upload-Metadata');

        if (empty($uploadMetaData)) {
            return '';
        }

        $uploadMetaDataChunks = explode(',', $uploadMetaData);

        foreach ($uploadMetaDataChunks as $chunk) {
            $pieces = explode(' ', trim($chunk));

            $key = $pieces[0];
            $value = $pieces[1] ?? '';

            if ($key === $requestedKey) {
                return base64_decode($value);
            }
        }

        return '';
    }

    /**
     * Extracts all meta data from the request header.
     *
     * @return string[]
     */
    public function extractAllMeta(): array
    {
        $uploadMetaData = $this->request->getHeaderLine('Upload-Metadata');

        if (empty($uploadMetaData)) {
            return [];
        }

        $uploadMetaDataChunks = explode(',', $uploadMetaData);

        $result = [];
        foreach ($uploadMetaDataChunks as $chunk) {
            $pieces = explode(' ', trim($chunk));

            $key = $pieces[0];
            $value = $pieces[1] ?? '';

            $result[$key] = base64_decode($value);
        }

        return $result;
    }

    /**
     * Extract partials from header.
     */
    public function extractPartials(): array
    {
        return $this->extractFromHeader('Upload-Concat', Server::UPLOAD_TYPE_FINAL . ';');
    }

    /**
     * Check if this is a partial upload request.
     */
    public function isPartial(): bool
    {
        return $this->header('Upload-Concat') === Server::UPLOAD_TYPE_PARTIAL;
    }

    /**
     * Check if this is a final concatenation request.
     */
    public function isFinal(): bool
    {
        return null !== ($header = $this->header('Upload-Concat')) && strpos($header, Server::UPLOAD_TYPE_FINAL . ';') !== false;
    }

    /**
     * Get request.
     */
    public function getRequest(): HyperfRequest
    {
        return $this->request;
    }

    /**
     * Validate file name.
     */
    protected function isValidFilename(string $filename): bool
    {
        $forbidden = ['../', '"', "'", '&', '/', '\\', '?', '#', ':'];

        foreach ($forbidden as $char) {
            if (strpos($filename, $char) !== false) {
                return false;
            }
        }

        return true;
    }
}
