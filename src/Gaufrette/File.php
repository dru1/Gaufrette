<?php

namespace Gaufrette;

use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Exception\FileNotFound;

/**
 * Points to a file in a filesystem
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class File
{
    protected $key;
    protected $filesystem;

    /**
     * Content variable is lazy. It will not be read from filesystem until it's requested first time
     * @var content
     */
    protected $content = null;

    /**
     * @var array metadata in associative array. Only for adapters that support metadata
     */
    protected $metadata = null;

    /**
     * Human readable filename (usually the end of the key)
     * @var string name
     */
    protected $name = null;

    /**
     * File size in bytes
     * @var int size
     */
    protected $size = 0;

    /**
     * Constructor
     *
     * @param string     $key
     * @param Filesystem $filesystem
     */
    public function __construct($key, Filesystem $filesystem)
    {
        $this->key = $key;
        $this->name = $key;
        $this->filesystem = $filesystem;
    }
    
    public function getFilesystem() {
        return $this->filesystem;
    }
    
    /**
     * Returns the key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Returns the content
     *
     * @throws Gaufrette\Exception\FileNotFound
     *
     * @param  array  $metadata optional metadata which should be send when read
     * @return string
     */
    public function getContent($metadata = array())
    {
        if (isset($this->content)) {
            return $this->content;
        }
        $this->setMetadata($metadata);

        return $this->content = $this->filesystem->read($this->key);
    }

    /**
     * @return string name of the file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int size of the file
     */
    public function getSize()
    {
        if ($this->size) {
            return $this->size;
        }

        try {
            return $this->size = Util\Size::fromContent($this->getContent());
        } catch (FileNotFound $exception) {
        }

        return 0;
    }

    /**
     * @param int size of the file
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * Sets the content
     *
     * @param string $content
     * @param array  $metadata optional metadata which should be send when write
     *
     * @return integer The number of bytes that were written into the file, or
     *                 FALSE on failure
     */
    public function setContent($content, $metadata = array())
    {
        $this->content = $content;
        $this->setMetadata($metadata);

        return $this->size = $this->filesystem->write($this->key, $this->content, true);
    }

    /**
     * @param string name of the file
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Indicates whether the file exists in the filesystem
     *
     * @return boolean
     */
    public function exists()
    {
        return $this->filesystem->has($this->key);
    }

    /**
     * Deletes the file from the filesystem
     *
     * @throws Gaufrette\Exception\FileNotFound
     * @throws \RuntimeException                when cannot delete file
     * @param  array                            $metadata optional metadata which should be send when write
     * @return boolean                          TRUE on success
     */
    public function delete($metadata = array())
    {
        $this->setMetadata($metadata);

        return $this->filesystem->delete($this->key);
    }

    /**
     * Creates a new file stream instance of the file
     *
     * @return FileStream
     */
    public function createStream()
    {
        return $this->filesystem->createStream($this->key);
    }

    /**
     * Sets the metadata array to be stored in adapters that can support it
     *
     * @param  array   $metadata
     * @return boolean
     */
    protected function setMetadata(array $metadata)
    {
        if ($metadata && $this->supportsMetadata()) {
            $this->filesystem->getAdapter()->setMetadata($this->key, $metadata);

            return true;
        }

        return false;
    }

    /**
     * @return boolean
     */
    private function supportsMetadata()
    {
        return $this->filesystem->getAdapter() instanceof MetadataSupporter;
    }
}
