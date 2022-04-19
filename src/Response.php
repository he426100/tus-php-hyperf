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

use Hyperf\HttpServer\Response as HyperfResponse;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class Response
{
    /** @var HyperfResponse */
    protected $response;

    /** @var bool */
    protected $createOnly = true;

    /** @var array */
    protected $headers = [];

    public function __construct(HyperfResponse $response)
    {
        $this->response = $response;
    }

    /**
     * Set create only.
     */
    public function createOnly(bool $state): self
    {
        $this->createOnly = $state;

        return $this;
    }

    /**
     * Set headers.
     *
     * @return Response
     */
    public function setHeaders(array $headers): self
    {
        $this->headers += $headers;

        return $this;
    }

    /**
     * Replace headers.
     *
     * @return Response
     */
    public function replaceHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Get global headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get create only.
     */
    public function getCreateOnly(): bool
    {
        return $this->createOnly;
    }

    /**
     * Create and send a response.
     *
     * @param mixed $content response data
     * @param int $status http status code
     * @param array $headers headers
     */
    public function send($content, int $status = HttpResponse::HTTP_OK, array $headers = []): PsrResponseInterface
    {
        $headers = array_merge($this->headers, $headers);

        if (\is_array($content)) {
            $response = $this->response->json($content);
        } else {
            $response = $this->response->raw($content);
        }

        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }
        return $response->withStatus($status);
    }

    /**
     * Create a new file download response.
     *
     * @param \SplFileInfo|string $file
     * @param null|string $disposition
     */
    public function download(
        $file,
        string $name = null,
        array $headers = [],
        string $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT
    ): PsrResponseInterface {
        $response = $this->response;
        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }
        return $response->download($file, $name);
    }
}
