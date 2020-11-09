<?php

declare(strict_types=1);

namespace Brick\Http;

/**
 * Builds collections of UploadedFile instances, potentially nested in sub-arrays.
 *
 * @psalm-immutable
 */
final class UploadedFileMap
{
    /**
     * Creates an UploadedFileMap from the $_FILES superglobal.
     *
     * @param array $files
     *
     * @return array
     */
    public static function createFromFilesGlobal(array $files) : array
    {
        $items = [];
        $files = self::normalizeSuperglobal($files);
        self::buildMap($files, $items);

        return $items;
    }

    /**
     * Normalizes a $_FILES superglobal.
     *
     * @param array $files
     *
     * @return array
     */
    private static function normalizeSuperglobal(array $files) : array
    {
        $result = [];

        foreach ($files as $file) {
            $keys = array_keys($file);

            foreach ($keys as $key) {
                $result[$key] = [];
                foreach ($files as $index => $file) {
                    $result[$key][$index] = $file[$key];
                }
            }

            // Only one of the entries was needed to get the PHP upload keys (name, tmp_name, etc.).
            break;
        }

        return $result;
    }

    /**
     * Recursively builds the map of UploadedFile instances.
     *
     * @param array $files
     * @param array $destination
     *
     * @return void
     */
    private static function buildMap(array $files, array & $destination) : void
    {
        foreach ($files as $structure) {
            foreach ($structure as $key => $value) {
                $subFiles = [];
                foreach ($files as $uploadKey => $data) {
                    $subFiles[$uploadKey] = $data[$key];
                }

                if (is_array($value)) {
                    $destination[$key] = [];
                    self::buildMap($subFiles, $destination[$key]);
                } else {
                    $destination[$key] = UploadedFile::create($subFiles);
                }
            }

            // Only one of the entries was needed to get the structure.
            break;
        }
    }
}
