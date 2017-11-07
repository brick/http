<?php

declare(strict_types=1);

namespace Brick\Http;

/**
 * Represents a file uploaded via an HTML form (RFC 1867).
 */
class UploadedFile
{
    /**
     * The temporary path of the uploaded file on the local filesystem.
     *
     * This can be an empty string if the upload is not valid.
     *
     * @var string
     */
    private $path;

    /**
     * The original file name, as sent by the browser.
     *
     * This can be an empty string if the upload is not valid.
     *
     * @var string
     */
    private $name;

    /**
     * The MIME type, as sent by the browser.
     *
     * This can be an empty string if the upload is not valid.
     *
     * @var string
     */
    private $type;

    /**
     * The size of the uploaded file.
     *
     * This can be zero if the uploaded file is empty or the upload is not valid.
     *
     * @var int
     */
    private $size;

    /**
     * The status of the upload, one of the `UPLOAD_ERR_*` constants.
     *
     * @var int
     */
    private $status;

    /**
     * Class constructor.
     *
     * @param string  $path   The path of the temporary file on the local filesystem.
     * @param string  $name   The original file name, as sent by the browser.
     * @param string  $type   The file MIME type, as sent by the browser.
     * @param int     $size   The size of the uploaded file.
     * @param int     $status The status of the upload, one of the UPLOAD_ERR_* constants.
     */
    private function __construct(string $path, string $name, string $type, int $size, int $status)
    {
        $this->path   = $path;
        $this->name   = $name;
        $this->type   = $type;
        $this->size   = $size;
        $this->status = $status;
    }

    /**
     * Creates an UploadedFile from an array, coming from the $_FILES superglobal.
     *
     * @param array $file
     *
     * @return UploadedFile
     */
    public static function create(array $file) : UploadedFile
    {
        return new UploadedFile(
            $file['tmp_name'],
            $file['name'],
            $file['type'],
            $file['size'],
            $file['error']
        );
    }

    /**
     * Returns the temporary path of the uploaded file on the local filesystem.
     *
     * This can be an empty string if the upload is not valid.
     *
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * Returns the original file name, as sent by the browser.
     *
     * This can be an empty string if the upload is not valid.
     *
     * As this value is client-originated,
     * it should always taken with caution and sanitized before use.
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Returns the extension of the original file name.
     *
     * This can be an empty string if the file name has no extension, or the upload is not valid.
     *
     * As this value is client-originated,
     * it should always taken with caution and validated before use.
     *
     * @return string
     */
    public function getExtension() : string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    /**
     * Returns the file MIME type, as sent by the browser.
     *
     * This can be an empty string if the upload is not valid.
     *
     * As this value is client-originated,
     * it should always taken with caution and validated before use.
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Returns the size of the uploaded file.
     *
     * This can be zero if the uploaded file is empty or the upload is not valid.
     *
     * @return int
     */
    public function getSize() : int
    {
        return $this->size;
    }

    /**
     * Returns the status of the upload, one of the UPLOAD_ERR_* constants.
     *
     * @return int
     */
    public function getStatus() : int
    {
        return $this->status;
    }

    /**
     * Returns whether the file upload is valid.
     *
     * This method should always be checked before attempting
     * to perform any processing on the uploaded file.
     *
     * @return bool
     */
    public function isValid() : bool
    {
        return $this->status === UPLOAD_ERR_OK;
    }

    /**
     * Returns whether a file was selected in the web form.
     *
     * @return bool
     */
    public function isSelected() : bool
    {
        return $this->status !== UPLOAD_ERR_NO_FILE;
    }
}
