<?php

namespace Ampersand\CurrencyConverter\Model\Currency\Import;

use Exception;
use GuzzleHttp\Client;
use Magento\Framework\Json\Helper\Data;
use Magento\Directory\Model\CurrencyFactory;
use Ampersand\CurrencyConverter\Helper\Config;
use Magento\Directory\Model\Currency\Import\AbstractImport;

class FreeCurrencyConverter extends AbstractImport
{
    const CURRENCY_CONVERTER_URL = 'https://free.currencyconverterapi.com/api/v3/convert?q={{CURRENCY_FROM}}_{{CURRENCY_TO}}';

    private $helper;
    private $jsonHelper;
    private $client;

    /**
     * FreeCurrencyConverter constructor.
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonHelper
     * @param \Ampersand\CurrencyConverter\Helper\Config $helper
     * @param \GuzzleHttp\Client $client
     */
    public function __construct(CurrencyFactory $currencyFactory, Data $jsonHelper, Config $helper, Client $client)
    {
        parent::__construct($currencyFactory);
        $this->jsonHelper = $jsonHelper;
        $this->helper = $helper;
        $this->client = $client;
    }

    /**
     * @param $currencyFrom
     * @param $currencyTo
     * @return string
     */
    private function replaceCurrencyInUrl($currencyFrom, $currencyTo)
    {
        $url = str_replace('{{CURRENCY_FROM}}', $currencyFrom, self::CURRENCY_CONVERTER_URL);
        return str_replace('{{CURRENCY_TO}}', $currencyTo, $url);
    }

    /**
     * @param $currencyFrom
     * @param $currencyTo
     * @return string
     */
    private function generateResultKey($currencyFrom, $currencyTo)
    {
        return $currencyFrom . '_' . $currencyTo;
    }

    /**
     * @param $body
     * @return array
     */
    private function getData($body)
    {
        return $this->jsonHelper->jsonDecode($body);
    }

    /**
     * @param $data
     * @return string
     */
    private function getQueryCount($data)
    {
        return $data['query']['count'];
    }

    /**
     * @param $data
     * @param $resultKey
     * @return array
     */
    private function getResultsArray($data, $resultKey)
    {
        return $data['results'][$resultKey];
    }

    /**
     * @param $results
     * @return float
     */
    private function convertValueToFloat($results)
    {
        return (float)$results['val'];
    }

    /**
     * @param string $currencyFrom
     * @param string $currencyTo
     * @param int $retry
     * @param null $result
     * @return float
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function _convert($currencyFrom, $currencyTo, $retry = 0, $result = null)
    {
        $requestUrl = $this->replaceCurrencyInUrl($currencyFrom, $currencyTo);
        try {
            $result = $this->client->request('GET', $requestUrl);
            $body = $result->getBody();
            $data = $this->getData($body);
            $resultKey = $this->generateResultKey($currencyFrom, $currencyTo);
            $results = $this->getResultsArray($data, $resultKey);
            if (!$this->getQueryCount($data) && !isset($results)) {
                $this->_messages[] = __('We can\'t retrieve a rate from %1.', $requestUrl);
            } else {
                $result = $this->convertValueToFloat($results);
            }
        } catch (Exception $e) {
            if ($retry == 0) {
                $this->_convert($currencyFrom, $currencyTo, 1, null);
            } else {
                $this->_messages[] = __('We can\'t retrieve a rate from %1.', $requestUrl);
            }
        }
        return $result;
    }
}
