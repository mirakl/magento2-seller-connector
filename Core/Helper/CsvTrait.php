<?php
namespace MiraklSeller\Core\Helper;

trait CsvTrait
{
    /**
     * If delimiter defined in key fails (CSV with 1 column) try to use fallbacks defined as value
     *
     * @var array
     */
    protected $_availableDelimiters = [
        ';' => [','],
    ];

    /**
     * @param   string  $str
     * @param   string  $enclosure
     * @return  \SplTempFileObject
     */
    public function createCsvFileFromString($str, $enclosure = '"')
    {
        $file = \Mirakl\create_temp_file($str);
        $file->setFlags(\SplFileObject::READ_CSV);
        $file->setCsvControl(';', $enclosure);

        $delimiters = $this->_availableDelimiters[';'];

        while (1 === count($file->fgetcsv()) && $delimiter = current($delimiters)) {
            $file->setCsvControl($delimiter, $enclosure);
            $file->rewind();
            next($delimiters);
        }

        $file->rewind();

        return $file;
    }

    /**
     * @param   \SplFileObject   $file
     * @return  bool
     */
    public function isCsvFileValid(\SplFileObject $file)
    {
        $file->rewind();

        return $file->fstat()['size'] > 0 && count($file->fgetcsv()) > 1;
    }
}