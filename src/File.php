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

use Carbon\Carbon;
use Tus\Cache\Cacheable;
use Tus\Exception\ConnectionException;
use Tus\Exception\FileException;
use Tus\Exception\OutOfRangeException;

class File
{
    /** @const Max chunk size */
    public const CHUNK_SIZE = 8192; // 8 kilobytes.

    /** @const Read binary mode */
    public const READ_BINARY = 'r';

    /** @const Append binary mode */
    public const APPEND_BINARY = 'a';

    /** @var string */
    protected $key;

    /** @var string */
    protected $checksum;

    /** @var string */
    protected $name;

    /** @var Cacheable */
    protected $cache;

    /** @var int */
    protected $offset;

    /** @var string */
    protected $location;

    /** @var string */
    protected $filePath;

    /** @var int */
    protected $fileSize;

    /** @var string[] */
    private $uploadMetadata = [];

    /**
     * File constructor.
     */
    public function __construct(string $name = null, Cacheable $cache = null)
    {
        $this->name = $name;
        $this->cache = $cache;
    }

    /**
     * Set file meta.
     *
     * @return File
     */
    public function setMeta(int $offset, int $fileSize, string $filePath, string $location = null): self
    {
        $this->offset = $offset;
        $this->fileSize = $fileSize;
        $this->filePath = $filePath;
        $this->location = $location;

        return $this;
    }

    /**
     * Set name.
     *
     * @return File
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set file size.
     *
     * @return File
     */
    public function setFileSize(int $size): self
    {
        $this->fileSize = $size;

        return $this;
    }

    /**
     * Get file size.
     */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    /**
     * Set key.
     *
     * @return File
     */
    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get key.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set checksum.
     *
     * @return File
     */
    public function setChecksum(string $checksum): self
    {
        $this->checksum = $checksum;

        return $this;
    }

    /**
     * Get checksum.
     */
    public function getChecksum(): string
    {
        return $this->checksum;
    }

    /**
     * Set offset.
     *
     * @return File
     */
    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Get offset.
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Set location.
     *
     * @return File
     */
    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Set absolute file location.
     *
     * @return File
     */
    public function setFilePath(string $path): self
    {
        $this->filePath = $path;

        return $this;
    }

    /**
     * Get absolute location.
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @param string[] $metadata
     *
     * @return File
     */
    public function setUploadMetadata(array $metadata): self
    {
        $this->uploadMetadata = $metadata;

        return $this;
    }

    /**
     * Get file meta.
     */
    public function details(): array
    {
        $now = Carbon::now();

        return [
            'name' => $this->name,
            'size' => $this->fileSize,
            'offset' => $this->offset,
            'checksum' => $this->checksum,
            'location' => $this->location,
            'file_path' => $this->filePath,
            'metadata' => $this->uploadMetadata,
            'created_at' => $now->format($this->cache::RFC_7231),
            'expires_at' => $now->addSeconds($this->cache->getTtl())->format($this->cache::RFC_7231),
        ];
    }

    /**
     * Upload file to server.
     *
     * @param $file
     *
     * @throws ConnectionException
     */
    public function upload($file, int $totalBytes): int
    {
        if ($this->offset === $totalBytes) {
            return $this->offset;
        }

        $output = $this->open($this->getFilePath(), self::APPEND_BINARY);
        $key = $this->getKey();

        try {
            $this->seek($output, $this->offset);

            if (connection_status() !== CONNECTION_NORMAL) {
                throw new ConnectionException('Connection aborted by user.');
            }
            $bytes = $this->write($output, $file);

            $this->offset += $bytes;

            $this->cache->set($key, ['offset' => $this->offset]);

            if ($this->offset > $totalBytes) {
                throw new OutOfRangeException('The uploaded file is corrupt.');
            }
        } finally {
            $this->close($output);
        }

        return $this->offset;
    }

    /**
     * Open file in given mode.
     *
     * @throws FileException
     *
     * @return resource
     */
    public function open(string $filePath, string $mode)
    {
        $this->exists($filePath, $mode);
        $ptr = @fopen($filePath, $mode);

        if ($ptr === false) {
            throw new FileException("Unable to open {$filePath}.");
        }
        return $ptr;
    }

    /**
     * Check if file to read exists.
     *
     * @throws FileException
     */
    public function exists(string $filePath, string $mode = self::READ_BINARY): bool
    {
        if ($mode === self::READ_BINARY && ! file_exists($filePath)) {
            throw new FileException('File not found.');
        }

        return true;
    }

    /**
     * Move file pointer to given offset.
     *
     * @param resource $handle
     *
     * @throws FileException
     */
    public function seek($handle, int $offset, int $whence = SEEK_SET): int
    {
        $position = fseek($handle, $offset, $whence);

        if ($position === -1) {
            throw new FileException('Cannot move pointer to desired position.');
        }

        return $position;
    }

    /**
     * Read data from file.
     *
     * @param resource $handle
     *
     * @throws FileException
     */
    public function read($handle, int $chunkSize): string
    {
        $data = fread($handle, $chunkSize);

        if ($data === false) {
            throw new FileException('Cannot read file.');
        }

        return $data;
    }

    /**
     * Write data to file.
     *
     * @param resource $handle
     * @param null|int $length
     *
     * @throws FileException
     */
    public function write($handle, string $data, $length = null): int
    {
        $bytesWritten = \is_int($length) ? fwrite($handle, $data, $length) : fwrite($handle, $data);

        if ($bytesWritten === false) {
            throw new FileException('Cannot write to a file.');
        }

        return $bytesWritten;
    }

    /**
     * Merge 2 or more files.
     *
     * @param array $files file data with meta info
     */
    public function merge(array $files): int
    {
        $destination = $this->getFilePath();
        $firstFile = array_shift($files);

        // First partial file can directly be copied.
        $this->copy($firstFile['file_path'], $destination);

        $this->offset = $firstFile['offset'];
        $this->fileSize = filesize($firstFile['file_path']);

        $handle = $this->open($destination, self::APPEND_BINARY);

        foreach ($files as $file) {
            if (! file_exists($file['file_path'])) {
                throw new FileException('File to be merged not found.');
            }

            $this->fileSize += $this->write($handle, file_get_contents($file['file_path']));

            $this->offset += $file['offset'];
        }

        $this->close($handle);

        return $this->fileSize;
    }

    /**
     * Copy file from source to destination.
     */
    public function copy(string $source, string $destination): bool
    {
        $status = @copy($source, $destination);

        if ($status === false) {
            throw new FileException(sprintf('Cannot copy source (%s) to destination (%s).', $source, $destination));
        }

        return $status;
    }

    /**
     * Delete file and/or folder.
     */
    public function delete(array $files, bool $folder = false): bool
    {
        $status = $this->deleteFiles($files);

        if ($status && $folder) {
            return rmdir(\dirname(current($files)));
        }

        return $status;
    }

    /**
     * Delete multiple files.
     */
    public function deleteFiles(array $files): bool
    {
        if (empty($files)) {
            return false;
        }

        $status = true;

        foreach ($files as $file) {
            if (file_exists($file)) {
                $status = $status && unlink($file);
            }
        }

        return $status;
    }

    /**
     * Close file.
     *
     * @param $handle
     */
    public function close($handle): bool
    {
        return fclose($handle);
    }
}
