<?php

namespace Tus;

use Hyperf\HttpServer\Response as HyperfResponse;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

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
     *
     * @param bool $state
     *
     * @return self
     */
    public function createOnly(bool $state): self
    {
        $this->createOnly = $state;

        return $this;
    }

    /**
     * Set headers.
     *
     * @param array $headers
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
     * @param array $headers
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
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get create only.
     *
     * @return bool
     */
    public function getCreateOnly(): bool
    {
        return $this->createOnly;
    }

    /**
     * Create and send a response.
     *
     * @param mixed $content Response data.
     * @param int   $status  Http status code.
     * @param array $headers Headers.
     *
     * @return PsrResponseInterface
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
     * @param string|null         $name
     * @param array               $headers
     * @param string|null         $disposition
     *
     * @return PsrResponseInterface
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
