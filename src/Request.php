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

use Tus\Tus\Server;
use Hyperf\HttpServer\Contract\RequestInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class Request
{
    /**
     * @var RequestInterface
     */
    protected $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Get http method from current request.
     * 
     * @return string
     */
    public function method(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Get the current path info for the request.
     * 
     * @return string
     */
    public function path(): string
    {
        return $this->request->getPathInfo();
    }

    /**
     * Get upload key from url.
     * 
     * @return string
     */
    public function key(): string
    {
        return basename($this->path());
    }

    /**
     * Supported http requests.
     * 
     * @return array
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
     * @param string               $key
     * @param string|string[]|null $default
     *
     * @return string|null
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
     * 
     * @return string
     */
    public function url(): string
    {
        return $this->request->url();
    }

    /**
     * Extract metadata from header.
     *
     * @param string $key
     * @param string $value
     *
     * @return array
     */
    public function extractFromHeader(string $key, string $value): array
    {
        $meta = $this->header($key);

        if (false !== strpos($meta, $value)) {
            $meta = trim(str_replace($value, '', $meta));

            return explode(' ', $meta) ?? [];
        }

        return [];
    }

    /**
     * Extract base64 encoded filename from header.
     * 
     * @return string
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
     *
     * @param string $requestedKey
     *
     * @return string
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
     * 
     * @return array
     */
    public function extractPartials(): array
    {
        return $this->extractFromHeader('Upload-Concat', Server::UPLOAD_TYPE_FINAL . ';');
    }

    /**
     * Check if this is a partial upload request.
     * 
     * @return bool
     */
    public function isPartial(): bool
    {
        return  Server::UPLOAD_TYPE_PARTIAL === $this->header('Upload-Concat');
    }

    /**
     * Check if this is a final concatenation request.
     * 
     * @return bool
     */
    public function isFinal(): bool
    {
        return null !== ($header = $this->header('Upload-Concat')) && false !== strpos($header, Server::UPLOAD_TYPE_FINAL . ';');
    }

    /**
     * Get request.
     * 
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Validate file name.
     *
     * @param string $filename
     *
     * @return bool
     */
    protected function isValidFilename(string $filename): bool
    {
        $forbidden = ['../', '"', "'", '&', '/', '\\', '?', '#', ':'];

        foreach ($forbidden as $char) {
            if (false !== strpos($filename, $char)) {
                return false;
            }
        }

        return true;
    }
}
