<?php
namespace HoneySens\app\patches;

use FileUpload\FileNameGenerator\Simple;
use FileUpload\FileSystem\FileSystem;
use FileUpload\FileUpload;
use FileUpload\PathResolver\PathResolver;
use FileUpload\Util;

/**
 * Upstream fixed a compatibility issue with PHP7+, but Packagist has not been updated yet.
 * This fixed implementation is taken from the upstream repository, see https://github.com/Gargron/fileupload/issues/87
 */
class SimpleFileNameGenerator extends Simple
{

    /**
     * Pathresolver
     * @var PathResolver
     */
    private $pathresolver;

    /**
     * Filesystem
     * @var FileSystem
     */
    private $filesystem;

    /**
     * Get file_name
     * @param  string     $source_name
     * @param  string     $type
     * @param  string     $tmp_name
     * @param  integer    $index
     * @param  string     $content_range
     * @param  FileUpload $upload
     * @return string
     */
    public function getFileName($source_name, $type, $tmp_name, $index, $content_range, FileUpload $upload)
    {
        $this->filesystem = $upload->getFileSystem();
        $this->pathresolver = $upload->getPathResolver();

        return ($this->getUniqueFilename($source_name, $type, $index, $content_range));
    }

    /**
     * Get unique but consistent name
     * @param  string  $name
     * @param  string  $type
     * @param  integer $index
     * @param  array   $content_range
     * @return string
     */
    protected function getUniqueFilename($name, $type, $index, $content_range)
    {
        if (! is_array($content_range)) {
            $content_range = [0];
        }

        while ($this->filesystem->isDir($this->pathresolver->getUploadPath($name))) {
            $name = $this->pathresolver->upcountName($name);
        }

        $uploaded_bytes = Util::fixIntegerOverflow(intval($content_range[1] ?? $content_range[0]));

        while ($this->filesystem->isFile($this->pathresolver->getUploadPath($name))) {
            if ($uploaded_bytes == $this->filesystem->getFilesize($this->pathresolver->getUploadPath($name))) {
                break;
            }

            $name = $this->pathresolver->upcountName($name);
        }

        return $name;
    }
}
