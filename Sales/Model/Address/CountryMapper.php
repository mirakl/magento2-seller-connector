<?php
namespace MiraklSeller\Sales\Model\Address;

use MiraklSeller\Sales\Helper\Config;

class CountryMapper
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $mapping;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Adds a country mapping if not existing yet
     *
     * @param   string  $countryLabel
     * @param   string  $countryId
     */
    public function add($countryLabel, $countryId = '')
    {
        $countryLabel = trim($countryLabel);
        $mapping = $this->get();

        if (empty($mapping[$countryLabel])) {
            $mapping[$countryLabel] = $countryId;
            $this->save($mapping);
        }
    }

    /**
     * Returns country labels mapping or associated country id if country label is specified
     *
     * @param   string|null $countryLabel
     * @return  array|false|string
     */
    public function get($countryLabel = null)
    {
        $this->load();

        if (null === $countryLabel) {
            return $this->mapping;
        }

        return !empty($this->mapping[$countryLabel])
            ? $this->mapping[$countryLabel]
            : false;
    }

    /**
     * Builds country labels mapping from config values like that:
     * <code>
     * [
     *     'France Métropolitaine' => 'FR',
     *     'USA' => 'US',
     *     ...
     * ]
     * </code>
     *
     * @return  void
     */
    public function load()
    {
        if (null !== $this->mapping) {
            return;
        }

        $this->mapping = [];

        foreach ($this->config->getCountryLabelsMapping() as $data) {
            if (isset($data['country_label']) && isset($data['country_id'])) {
                $this->mapping[trim($data['country_label'])] = $data['country_id'];
            }
        }
    }

    /**
     * @param   array   $mapping
     */
    public function save(array $mapping)
    {
        $data = [];

        foreach ($mapping as $countryLabel => $countryId) {
            $data[uniqid('_')] = [
                'country_id'    => $countryId,
                'country_label' => $countryLabel,
            ];
        }

        $this->config->saveCountryLabelsMapping($data);
    }
}
