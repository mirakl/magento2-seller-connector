<?php
namespace MiraklSeller\Sales\Model\Mapper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class CountryNotFoundException extends LocalizedException
{
    /**
     * @var string
     */
    protected $country;

    /**
     * @param Phrase          $phrase
     * @param \Exception|null $cause
     * @param int             $code
     * @param string          $country
     */
    public function __construct(Phrase $phrase, \Exception $cause = null, $code = 0, $country = '')
    {
        parent::__construct($phrase, $cause, $code);
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }
}