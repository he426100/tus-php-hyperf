<?php

declare(strict_types=1);
/**
 * This file is part of he426100/tus-php-hyperf.
 *
 * @link     https://github.com/he426100/tus-php-hyperf
 * @contact  mrpzx001@gmail.com
 * @license  https://github.com/he426100/tus-php-hyperf/blob/master/LICENSE
 */
namespace Tus\Tus;

use Carbon\Carbon;
use Hyperf\Utils\Context;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tus\Cache\Cacheable;
use Tus\Cache\CacheFactory;
use Tus\Event\UploadComplete;
use Tus\Event\UploadCreated;
use Tus\Event\UploadMerged;
use Tus\Event\UploadProgress;
use Tus\Exception\ConnectionException;
use Tus\Exception\FileException;
use Tus\Exception\OutOfRangeException;
use Tus\File;
use Tus\Middleware\Middleware;
use Tus\Request;
use Tus\Response;

class Server extends AbstractTus
{
    /** @const string Tus Creation Extension */
    public const TUS_EXTENSION_CREATION = 'creation';

    /** @const string Tus Termination Extension */
    public const TUS_EXTENSION_TERMINATION = 'termination';

    /** @const string Tus Checksum Extension */
    public const TUS_EXTENSION_CHECKSUM = 'checksum';

    /** @const string Tus Expiration Extension */
    public const TUS_EXTENSION_EXPIRATION = 'expiration';

    /** @const string Tus Concatenation Extension */
    public const TUS_EXTENSION_CONCATENATION = 'concatenation';

    /** @const array All supported tus extensions */
    public const TUS_EXTENSIONS = [
        self::TUS_EXTENSION_CREATION,
        self::TUS_EXTENSION_TERMINATION,
        self::TUS_EXTENSION_CHECKSUM,
        self::TUS_EXTENSION_EXPIRATION,
        self::TUS_EXTENSION_CONCATENATION,
    ];

    /** @const int 460 Checksum Mismatch */
    private const HTTP_CHECKSUM_MISMATCH = 460;

    /** @const string Default checksum algorithm */
    private const DEFAULT_CHECKSUM_ALGORITHM = 'sha256';

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /** @var string */
    protected $uploadDir;

    /** @var string */
    protected $uploadKey;

    /** @var Middleware */
    protected $middleware;

    /** @var Cacheable */
    protected $cache;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @var int max upload size in bytes
     *          Default 0, no restriction
     */
    protected $maxUploadSize = 0;

    /**
     * TusServer constructor.
     *
     * @param Cacheable|string $cacheAdapter
     *
     * @throws \ReflectionException
     */
    public function __construct(Request $request, Response $response, Middleware $middleware, EventDispatcherInterface $dispatcher, CacheFactory $cacheFactory)
    {
        $this->request = $request;
        $this->response = $response;
        $this->middleware = $middleware;
        $this->uploadDir = \dirname(__DIR__, 2) . '/' . 'uploads';
        $this->cache = $cacheFactory->make();
        $this->cache->setPrefix($this->getCachePrefix());

        $this->dispatcher = $dispatcher;
    }

    /**
     * No other methods are allowed.
     *
     * @return PsrResponseInterface
     */
    public function __call(string $method, array $params)
    {
        return $this->response->send(null, HttpResponse::HTTP_BAD_REQUEST);
    }

    /**
     * Get cache.
     */
    public function getCache(): Cacheable
    {
        return $this->cache;
    }

    /**
     * Set and get event dispatcher.
     */
    public function event(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    /**
     * Set upload dir.
     *
     * @return Server
     */
    public function setUploadDir(string $path): self
    {
        $this->uploadDir = $path;

        return $this;
    }

    /**
     * Get upload dir.
     */
    public function getUploadDir(): string
    {
        return $this->uploadDir;
    }

    /**
     * Get request.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get request.
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Get file checksum.
     */
    public function getServerChecksum(string $filePath): string
    {
        return hash_file($this->getChecksumAlgorithm(), $filePath);
    }

    /**
     * Get checksum algorithm.
     */
    public function getChecksumAlgorithm(): ?string
    {
        $checksumHeader = $this->getRequest()->header('Upload-Checksum');

        if (empty($checksumHeader)) {
            return self::DEFAULT_CHECKSUM_ALGORITHM;
        }

        [$checksumAlgorithm, /* $checksum */] = explode(' ', $checksumHeader);

        return $checksumAlgorithm;
    }

    /**
     * Set upload key.
     *
     * @return Server
     */
    public function setUploadKey(string $key): self
    {
        $this->uploadKey = $key;

        return $this;
    }

    /**
     * Get upload key from header.
     *
     * @return PsrResponseInterface|string
     */
    public function getUploadKey()
    {
        if (! empty($this->uploadKey)) {
            return $this->uploadKey;
        }

        $key = $this->getRequest()->header('Upload-Key') ?? Uuid::uuid4()->toString();

        if (empty($key)) {
            return $this->response->send(null, HttpResponse::HTTP_BAD_REQUEST);
        }

        $this->uploadKey = $key;

        return $this->uploadKey;
    }

    /**
     * Set middleware.
     */
    public function setMiddleware(Middleware $middleware): self
    {
        $this->middleware = $middleware;

        return $this;
    }

    /**
     * Get middleware.
     */
    public function middleware(): Middleware
    {
        return $this->middleware;
    }

    /**
     * Set max upload size in bytes.
     *
     * @return Server
     */
    public function setMaxUploadSize(int $uploadSize): self
    {
        $this->maxUploadSize = $uploadSize;

        return $this;
    }

    /**
     * Get max upload size.
     */
    public function getMaxUploadSize(): int
    {
        return $this->maxUploadSize;
    }

    /**
     * Handle all HTTP request.
     *
     * @return PsrResponseInterface
     */
    public function serve()
    {
        $this->applyMiddleware();

        $requestMethod = $this->getRequest()->method();

        if (! \in_array($requestMethod, $this->getRequest()->allowedHttpVerbs(), true)) {
            return $this->response->send(null, HttpResponse::HTTP_METHOD_NOT_ALLOWED);
        }

        $clientVersion = $this->getRequest()->header('Tus-Resumable');

        if ($requestMethod !== HttpRequest::METHOD_OPTIONS && $clientVersion && $clientVersion !== self::TUS_PROTOCOL_VERSION) {
            return $this->response->send(null, HttpResponse::HTTP_PRECONDITION_FAILED, [
                'Tus-Version' => self::TUS_PROTOCOL_VERSION,
            ]);
        }

        $method = 'handle' . ucfirst(strtolower($requestMethod));
        return $this->{$method}();
    }

    /**
     * Delete expired resources.
     */
    public function handleExpiration(): array
    {
        $deleted = [];
        $cacheKeys = $this->cache->keys('*');

        foreach ($cacheKeys as $key) {
            $fileMeta = $this->cache->get($key, true);

            if (! $this->isExpired($fileMeta)) {
                continue;
            }

            if (! $this->cache->delete($key)) {
                continue;
            }

            if (is_writable($fileMeta['file_path'])) {
                unlink($fileMeta['file_path']);
            }

            $deleted[] = $fileMeta;
        }

        return $deleted;
    }

    /**
     * get cache prefix.
     */
    public function getCachePrefix(): string
    {
        return 'tus:' . strtolower((new \ReflectionClass(static::class))->getShortName()) . ':';
    }

    /**
     * Apply middleware.
     */
    protected function applyMiddleware()
    {
        $middleware = $this->middleware()->list();

        foreach ($middleware as $m) {
            $m->handle($this->getRequest(), $this->getResponse());
        }
    }

    /**
     * Handle OPTIONS request.
     */
    protected function handleOptions(): PsrResponseInterface
    {
        $headers = [
            'Allow' => implode(',', $this->request->allowedHttpVerbs()),
            'Tus-Version' => self::TUS_PROTOCOL_VERSION,
            'Tus-Extension' => implode(',', self::TUS_EXTENSIONS),
            'Tus-Checksum-Algorithm' => $this->getSupportedHashAlgorithms(),
        ];

        $maxUploadSize = $this->getMaxUploadSize();

        if ($maxUploadSize > 0) {
            $headers['Tus-Max-Size'] = $maxUploadSize;
        }

        return $this->response->send(null, HttpResponse::HTTP_OK, $headers);
    }

    /**
     * Handle HEAD request.
     */
    protected function handleHead(): PsrResponseInterface
    {
        $key = $this->request->key();

        if (! $fileMeta = $this->cache->get($key)) {
            return $this->response->send(null, HttpResponse::HTTP_NOT_FOUND);
        }

        $offset = $fileMeta['offset'] ?? false;

        if ($offset === false) {
            return $this->response->send(null, HttpResponse::HTTP_GONE);
        }
        return $this->response->send(null, HttpResponse::HTTP_OK, $this->getHeadersForHeadRequest($fileMeta));
    }

    /**
     * Handle POST request.
     */
    protected function handlePost(): PsrResponseInterface
    {
        $fileName = $this->getRequest()->extractFileName();
        $uploadType = self::UPLOAD_TYPE_NORMAL;

        if (empty($fileName)) {
            return $this->response->send(null, HttpResponse::HTTP_BAD_REQUEST);
        }

        if (! $this->verifyUploadSize()) {
            return $this->response->send(null, HttpResponse::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        $uploadKey = $this->getUploadKey();
        $filePath = $this->uploadDir . '/' . $fileName;

        if ($this->getRequest()->isFinal()) {
            return $this->handleConcatenation($fileName, $filePath);
        }

        if ($this->getRequest()->isPartial()) {
            $filePath = $this->getPathForPartialUpload($uploadKey) . $fileName;
            $uploadType = self::UPLOAD_TYPE_PARTIAL;
        }

        $checksum = $this->getClientChecksum();
        $location = $this->getRequest()->url() . $this->getApiPath() . '/' . $uploadKey;

        $file = $this->buildFile([
            'name' => $fileName,
            'offset' => 0,
            'size' => (int)$this->getRequest()->header('Upload-Length'),
            'file_path' => $filePath,
            'location' => $location,
        ])->setKey($uploadKey)->setChecksum($checksum)->setUploadMetadata($this->getRequest()->extractAllMeta());

        $this->cache->set($uploadKey, $file->details() + ['upload_type' => $uploadType]);

        $headers = [
            'Location' => $location,
            'Upload-Expires' => $this->cache->get($uploadKey)['expires_at'],
        ];

        $this->dispatcher->dispatch(
            new UploadCreated($file, $this->getRequest(), $this->getResponse()->setHeaders($headers)),
            UploadCreated::NAME
        );

        return $this->response->send(null, HttpResponse::HTTP_CREATED, $headers);
    }

    /**
     * Handle file concatenation.
     */
    protected function handleConcatenation(string $fileName, string $filePath): PsrResponseInterface
    {
        $partials = $this->getRequest()->extractPartials();
        $uploadKey = $this->getUploadKey();
        $files = $this->getPartialsMeta($partials);
        $filePaths = array_column($files, 'file_path');
        $location = $this->getRequest()->url() . $this->getApiPath() . '/' . $uploadKey;

        $file = $this->buildFile([
            'name' => $fileName,
            'offset' => 0,
            'size' => 0,
            'file_path' => $filePath,
            'location' => $location,
        ])->setFilePath($filePath)->setKey($uploadKey)->setUploadMetadata($this->getRequest()->extractAllMeta());

        $file->setOffset($file->merge($files));

        // Verify checksum.
        $checksum = $this->getServerChecksum($filePath);

        if ($checksum !== $this->getClientChecksum()) {
            return $this->response->send(null, self::HTTP_CHECKSUM_MISMATCH);
        }

        $file->setChecksum($checksum);
        $this->cache->set($uploadKey, $file->details() + ['upload_type' => self::UPLOAD_TYPE_FINAL]);

        // Cleanup.
        if ($file->delete($filePaths, true)) {
            $this->cache->deleteAll($partials);
        }

        $this->event()->dispatch(
            new UploadMerged($file, $this->getRequest(), $this->getResponse()),
            UploadMerged::NAME
        );

        return $this->response->send(
            ['data' => ['checksum' => $checksum]],
            HttpResponse::HTTP_CREATED,
            [
                'Location' => $location,
            ]
        );
    }

    /**
     * Handle PATCH request.
     */
    protected function handlePatch(): PsrResponseInterface
    {
        $uploadKey = $this->request->key();

        if (! $meta = $this->cache->get($uploadKey)) {
            return $this->response->send(null, HttpResponse::HTTP_GONE);
        }

        $status = $this->verifyPatchRequest($meta);

        if ($status !== HttpResponse::HTTP_OK) {
            return $this->response->send(null, $status);
        }

        $file = $this->buildFile($meta)->setUploadMetadata($meta['metadata'] ?? []);
        $checksum = $meta['checksum'];
        try {
            $request = Context::get(ServerRequestInterface::class);
            $swooleRequest = $request->getSwooleRequest();
            $uploadedContent = $swooleRequest->getContent();
            $fileSize = $file->getFileSize();
            $offset = $file->setKey($uploadKey)->setChecksum($checksum)->upload($uploadedContent, $fileSize);
            // If upload is done, verify checksum.
            if ($offset === $fileSize) {
                if (! $this->verifyChecksum($checksum, $meta['file_path'])) {
                    return $this->response->send(null, self::HTTP_CHECKSUM_MISMATCH);
                }

                $this->event()->dispatch(
                    new UploadComplete($file, $this->getRequest(), $this->getResponse()),
                    UploadComplete::NAME
                );
            } else {
                $this->event()->dispatch(
                    new UploadProgress($file, $this->getRequest(), $this->getResponse()),
                    UploadProgress::NAME
                );
            }
        } catch (FileException $e) {
            return $this->response->send($e->getMessage(), HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (OutOfRangeException $e) {
            return $this->response->send(null, HttpResponse::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE);
        } catch (ConnectionException $e) {
            return $this->response->send(null, HttpResponse::HTTP_CONTINUE);
        }

        return $this->response->send(null, HttpResponse::HTTP_NO_CONTENT, [
            'Content-Type' => self::HEADER_CONTENT_TYPE,
            'Upload-Expires' => $this->cache->get($uploadKey)['expires_at'],
            'Upload-Offset' => $offset,
        ]);
    }

    /**
     * Verify PATCH request.
     */
    protected function verifyPatchRequest(array $meta): int
    {
        if ($meta['upload_type'] === self::UPLOAD_TYPE_FINAL) {
            return HttpResponse::HTTP_FORBIDDEN;
        }

        $uploadOffset = $this->request->header('upload-offset');

        if ($uploadOffset && $uploadOffset !== (string) $meta['offset']) {
            return HttpResponse::HTTP_CONFLICT;
        }

        $contentType = $this->request->header('Content-Type');

        if ($contentType !== self::HEADER_CONTENT_TYPE) {
            return HTTPRESPONSE::HTTP_UNSUPPORTED_MEDIA_TYPE;
        }

        return HttpResponse::HTTP_OK;
    }

    /**
     * Handle GET request.
     *
     * As per RFC7231, we need to treat HEAD and GET as an identical request.
     * All major PHP frameworks follows the same and silently transforms each
     * HEAD requests to GET.
     *
     * @return PsrResponseInterface
     */
    protected function handleGet()
    {
        // We will treat '/files/<key>/get' as a download request.
        if ($this->request->key() === 'get') {
            return $this->handleDownload();
        }

        return $this->handleHead();
    }

    /**
     * Handle Download request.
     *
     * @return PsrResponseInterface
     */
    protected function handleDownload()
    {
        $path = explode('/', str_replace('/get', '', $this->request->path()));
        $key = end($path);

        if (! $fileMeta = $this->cache->get($key)) {
            return $this->response->send('404 upload not found.', HttpResponse::HTTP_NOT_FOUND);
        }

        $resource = $fileMeta['file_path'] ?? null;
        $fileName = $fileMeta['name'] ?? null;

        if (! $resource || ! file_exists($resource)) {
            return $this->response->send('404 upload not found.', HttpResponse::HTTP_NOT_FOUND);
        }

        return $this->response->download($resource, $fileName);
    }

    /**
     * Handle DELETE request.
     */
    protected function handleDelete(): PsrResponseInterface
    {
        $key = $this->request->key();
        $fileMeta = $this->cache->get($key);
        $resource = $fileMeta['file_path'] ?? null;

        if (! $resource) {
            return $this->response->send(null, HttpResponse::HTTP_NOT_FOUND);
        }

        $isDeleted = $this->cache->delete($key);

        if (! $isDeleted || ! file_exists($resource)) {
            return $this->response->send(null, HttpResponse::HTTP_GONE);
        }

        unlink($resource);

        return $this->response->send(null, HttpResponse::HTTP_NO_CONTENT, [
            'Tus-Extension' => self::TUS_EXTENSION_TERMINATION,
        ]);
    }

    /**
     * Get required headers for head request.
     */
    protected function getHeadersForHeadRequest(array $fileMeta): array
    {
        $headers = [
            'Upload-Length' => (int) $fileMeta['size'],
            'Upload-Offset' => (int) $fileMeta['offset'],
            'Cache-Control' => 'no-store',
        ];

        if ($fileMeta['upload_type'] === self::UPLOAD_TYPE_FINAL && $fileMeta['size'] !== $fileMeta['offset']) {
            unset($headers['Upload-Offset']);
        }

        if ($fileMeta['upload_type'] !== self::UPLOAD_TYPE_NORMAL) {
            $headers += ['Upload-Concat' => $fileMeta['upload_type']];
        }

        return $headers;
    }

    /**
     * Build file object.
     */
    protected function buildFile(array $meta): File
    {
        $file = new File($meta['name'], $this->cache);

        if (\array_key_exists('offset', $meta)) {
            $file->setMeta($meta['offset'], $meta['size'], $meta['file_path'], $meta['location']);
        }

        return $file;
    }

    /**
     * Get list of supported hash algorithms.
     */
    protected function getSupportedHashAlgorithms(): string
    {
        $supportedAlgorithms = hash_algos();

        $algorithms = [];
        foreach ($supportedAlgorithms as $hashAlgo) {
            if (strpos($hashAlgo, ',') !== false) {
                $algorithms[] = "'{$hashAlgo}'";
            } else {
                $algorithms[] = $hashAlgo;
            }
        }

        return implode(',', $algorithms);
    }

    /**
     * Verify and get upload checksum from header.
     *
     * @return PsrResponseInterface|string
     */
    protected function getClientChecksum()
    {
        $checksumHeader = $this->getRequest()->header('Upload-Checksum');

        if (empty($checksumHeader)) {
            return '';
        }

        [$checksumAlgorithm, $checksum] = explode(' ', $checksumHeader);

        $checksum = base64_decode($checksum);

        if ($checksum === false || ! \in_array($checksumAlgorithm, hash_algos(), true)) {
            return $this->response->send(null, HttpResponse::HTTP_BAD_REQUEST);
        }

        return $checksum;
    }

    /**
     * Get expired but incomplete uploads.
     *
     * @param null|array $contents
     */
    protected function isExpired($contents): bool
    {
        if (empty($contents)) {
            return true;
        }

        $isExpired = empty($contents['expires_at']) || Carbon::parse($contents['expires_at'])->lt(Carbon::now());

        if ($isExpired && $contents['offset'] !== $contents['size']) {
            return true;
        }

        return false;
    }

    /**
     * Get path for partial upload.
     */
    protected function getPathForPartialUpload(string $key): string
    {
        [$actualKey, /* $partialUploadKey */] = explode(self::PARTIAL_UPLOAD_NAME_SEPARATOR, $key);

        $path = $this->uploadDir . '/' . $actualKey . '/';

        if (! file_exists($path)) {
            mkdir($path);
        }

        return $path;
    }

    /**
     * Get metadata of partials.
     */
    protected function getPartialsMeta(array $partials): array
    {
        $files = [];

        foreach ($partials as $partial) {
            $fileMeta = $this->cache->get($partial);

            $files[] = $fileMeta;
        }

        return $files;
    }

    /**
     * Verify max upload size.
     */
    protected function verifyUploadSize(): bool
    {
        $maxUploadSize = $this->getMaxUploadSize();

        if ($maxUploadSize > 0 && $this->getRequest()->header('Upload-Length') > $maxUploadSize) {
            return false;
        }

        return true;
    }

    /**
     * Verify checksum if available.
     */
    protected function verifyChecksum(string $checksum, string $filePath): bool
    {
        // Skip if checksum is empty.
        if (empty($checksum)) {
            return true;
        }

        return $checksum === $this->getServerChecksum($filePath);
    }
}
