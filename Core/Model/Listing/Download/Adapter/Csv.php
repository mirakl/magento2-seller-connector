<?php
namespace MiraklSeller\Core\Model\Listing\Download\Adapter;

class Csv extends \SplTempFileObject
    implements AdapterInterface
{
    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @param   int|null    $maxMemory
     * @param   string      $delimiter
     * @param   string      $enclosure
     */
    public function __construct($maxMemory = null, $delimiter = ';', $enclosure = '"')
    {
        parent::__construct(is_int($maxMemory) ? $maxMemory : null);
        $this->setCsvControl($delimiter, $enclosure);
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        $this->rewind();

        return $this->fread($this->fstat()['size']);
    }

    /**
     * {@inheritdoc}
     */
    public function getFileExtension()
    {
        return 'csv';
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data)
    {
        if (0 === $this->count) {
            $this->fputcsv(array_keys($data));
        }
        $this->count++;

        return $this->fputcsv($data);
    }
}