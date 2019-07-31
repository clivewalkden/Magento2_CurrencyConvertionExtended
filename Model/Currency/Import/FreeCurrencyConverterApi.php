<?php

namespace Sozo\CurrencyConversionExtended\Model\Currency\Import;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\Currency\Import\AbstractImport;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Store\Model\ScopeInterface;

/**
 * Currency rate import model (From https://free.currencyconverterapi.com/)
 */
class FreeCurrencyConverterApi extends AbstractImport
{
    /**
     * @var string
     */
    const CURRENCY_CONVERTER_URL = 'https://free.currconv.com/api/v7/convert?apiKey={{API_KEY}}&q={{CURRENCY_FROM}}_{{CURRENCY_TO}}&compact=ultra';

    /**
     * Http Client Factory
     *
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * Core scope config
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * The API Key
     *
     * @var string
     */
    protected $accessKey;

    /**
     * Initialize dependencies
     *
     * @param CurrencyFactory $currencyFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ZendClientFactory $httpClientFactory
     */
    public function __construct(
        CurrencyFactory $currencyFactory,
        ScopeConfigInterface $scopeConfig,
        ZendClientFactory $httpClientFactory
    )
    {
        parent::__construct($currencyFactory);
        $this->scopeConfig = $scopeConfig;
        $this->httpClientFactory = $httpClientFactory;

        $this->accessKey = $this->scopeConfig->getValue(
            'currency/currency_converter/api_key',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fetchRates()
    {
        $data = [];
        $currencies = $this->_getCurrencyCodes();
        $defaultCurrencies = $this->_getDefaultCurrencyCodes();
        foreach ($defaultCurrencies as $currencyFrom) {
            if (!isset($data[$currencyFrom])) {
                $data[$currencyFrom] = [];
            }
            $data = $this->convertBatch($data, $currencyFrom, $currencies);
            ksort($data[$currencyFrom]);
        }
        return $data;
    }

    /**
     * Return currencies convert rates in batch mode
     *
     * @param array $data
     * @param string $currencyFrom
     * @param array $currenciesTo
     * @return array
     */
    private function convertBatch($data, $currencyFrom, $currenciesTo)
    {
        if (empty($this->accessKey)) {
            $this->_messages[] = __('No API key was specified or an invalid API key was specified');
            $data[$currencyFrom] = $this->makeEmptyResponse($currenciesTo);
            return $data;
        }

        foreach ($currenciesTo as $to) {
            set_time_limit(0);
            try {
                $url = $this->prepareUrl($currencyFrom, $to);
                $response = $this->getServiceResponse($url);
                $this->processResponse($data, $currencyFrom, $to, $url, $response);
            } finally {
                ini_restore('max_execution_time');
            }
        }
        return $data;
    }

    /**
     * Prepare the URL
     *
     * @param float $currencyFrom
     * @param float $currencyTo
     * @return string $url
     */
    public function prepareUrl($currencyFrom, $currencyTo)
    {
        $url = str_replace('{{API_KEY}}', $this->accessKey, self::CURRENCY_CONVERTER_URL);
        $url = str_replace('{{CURRENCY_FROM}}', $currencyFrom, $url);
        $url = str_replace('{{CURRENCY_TO}}', $currencyTo, $url);

        return $url;
    }

    /**
     * Get CurrencyConverterApi service response
     *
     * @param string $url
     * @param int $retry
     * @return array
     */
    private function getServiceResponse($url, $retry = 0)
    {
        /** @var \Magento\Framework\HTTP\ZendClient $httpClient */
        $httpClient = $this->httpClientFactory->create();
        $response = [];
        try {
            $jsonResponse = $httpClient->setUri(
                $url
            )->setConfig(
                [
                    'timeout' => $this->scopeConfig->getValue(
                        'currency/currency_converter/timeout',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    ),
                ]
            )->request(
                'GET'
            )->getBody();
            $response = json_decode($jsonResponse, true);
        } catch (\Exception $e) {
            if ($retry == 0) {
                $response = $this->getServiceResponse($url, 1);
            }
        }
        return $response;
    }

    /**
     * Process the result
     *
     * @param array $data
     * @param string $currencyFrom
     * @param string $currencyTo
     * @param string $url
     * @param array $response
     * @return mixed
     */
    protected function processResponse(&$data, $currencyFrom, $currencyTo, $url, $response)
    {
        if ($currencyFrom == $currencyTo) {
            $data[$currencyFrom][$currencyTo] = $this->_numberFormat(1);
        } else {
            if (empty($response)) {
                $this->_messages[] = __('We can\'t retrieve a rate from %1 for %2.', $url, $currencyTo);
                $data[$currencyFrom][$currencyTo] = null;
            } elseif (isset($response['status']) && $response['status'] == 400) {
                $this->_messages[] = __($response['error']);
                $data[$currencyFrom][$currencyTo] = null;
            } else {
                $data[$currencyFrom][$currencyTo] = $this->_numberFormat(
                    (double)$response[$currencyFrom . '_' . $currencyTo]
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _convert($currencyFrom, $currencyTo)
    {
        return 1;
    }
}