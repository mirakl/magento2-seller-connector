<?php
namespace MiraklSeller\Api\Test\Integration;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * @param   string  $fileName
     * @return  bool|string
     */
    protected function _getFileContents($fileName)
    {
        return file_get_contents($this->_getFilePath($fileName));
    }

    /**
     * @return  string
     */
    protected function _getFilesDir()
    {
        return realpath(dirname((new \ReflectionClass(static::class))->getFileName()) . '/_files');
    }

    /**
     * @param   string  $file
     * @return  string
     */
    protected function _getFilePath($file)
    {
        return $this->_getFilesDir() . '/' . $file;
    }

    /**
     * @param   string  $fileName
     * @param   bool    $assoc
     * @return  array
     */
    protected function _getJsonFileContents($fileName, $assoc = true)
    {
        return json_decode($this->_getFileContents($fileName), $assoc);
    }
}