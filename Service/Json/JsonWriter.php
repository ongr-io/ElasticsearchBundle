<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Service\Json;

/**
 * Serializes records one by one. Outputs given metadata before first record.
 *
 * Sample output:
 * <p>
 * [
 * {"count":2},
 * {"_id":"doc1","title":"Document 1"},
 * {"_id":"doc2","title":"Document 2"}
 * ]
 * </p>
 */
class JsonWriter
{
    private $filename;
    private $handle;
    private $count;
    private $currentPosition = 0;

    public function __construct(string $filename, int $count)
    {
        $this->filename = $filename;
        $this->count = $count;
    }

    /**
     * Destructor. Closes file handler if open.
     */
    public function __destruct()
    {
        $this->finalize();
    }

    /**
     * Performs initialization.
     */
    protected function initialize()
    {
        if ($this->handle !== null) {
            return;
        }

        $this->handle = fopen($this->filename, 'w');
        fwrite($this->handle, "[\n");
        fwrite($this->handle, json_encode(['count' => $this->count]));
    }

    /**
     * Performs finalization.
     */
    public function finalize()
    {
        $this->initialize();

        if (is_resource($this->handle)) {
            fwrite($this->handle, "\n]");
            fclose($this->handle);
        }
    }

    /**
     * Writes single document to stream.
     *
     * @param mixed $document Object to insert into stream.
     *
     * @throws \OverflowException
     */
    public function push($document)
    {
        $this->initialize();
        $this->currentPosition++;

        if (isset($this->count) && $this->currentPosition > $this->count) {
            throw new \OverflowException(
                sprintf('This writer was set up to write %d documents, got more.', $this->count)
            );
        }

        fwrite($this->handle, ",\n");
        fwrite($this->handle, json_encode($document));

        if (isset($this->count) && $this->currentPosition == $this->count) {
            $this->finalize();
        }
    }
}
