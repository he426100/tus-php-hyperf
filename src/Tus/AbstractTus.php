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

abstract class AbstractTus
{
    /** @const string Tus protocol version. */
    public const TUS_PROTOCOL_VERSION = '1.0.0';

    /** @const string Upload type partial. */
    public const UPLOAD_TYPE_PARTIAL = 'partial';

    /** @const string Upload type final. */
    public const UPLOAD_TYPE_FINAL = 'final';

    /** @const string Name separator for partial upload. */
    protected const PARTIAL_UPLOAD_NAME_SEPARATOR = '_';

    /** @const string Upload type normal. */
    protected const UPLOAD_TYPE_NORMAL = 'normal';

    /** @const string Header Content Type */
    protected const HEADER_CONTENT_TYPE = 'application/offset+octet-stream';

    /** @var string */
    protected $apiPath = '/files';

    /**
     * Set API path.
     */
    public function setApiPath(string $path): self
    {
        $this->apiPath = $path;

        return $this;
    }

    /**
     * Get API path.
     */
    public function getApiPath(): string
    {
        return $this->apiPath;
    }
}
