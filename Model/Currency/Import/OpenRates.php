<?php

namespace Sozo\CurrencyConversionExtended\Model\Currency\Import;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\Currency\Import\AbstractImport;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Store\Model\ScopeInterface;

/**
 * Currency rate import model (From https://openrates.io/)
 */
class OpenRates extends AbstractImport
{
    /**
     * @var string
     */
    const CURRENCY_CONVERTER_URL = 'https://api.exchangeratesapi.io/latest?base={{CURRENCY_FROM}}&symbols={{CURRENCY_TO}}';

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
        foreach ($currenciesTo as $to) {
            set_time_limit(0);
            try {
                $url = $this->prepareUrl($currencyFrom, $to);
                $response = $this->getServiceResponse($url);
                $data = $this->processResponse($currencyFrom, $to, $url, $response);
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
    private function prepareUrl($currencyFrom, $currencyTo)
    {
        $url = str_replace('{{CURRENCY_FROM}}', $currencyFrom, self::CURRENCY_CONVERTER_URL);
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
                        'currency/open_rates/timeout',
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
     * @param float $currencyFrom
     * @param float $currencyTo
     * @param string $url
     * @param array $response
     * @return mixed
     */
    private function processResponse($currencyFrom, $currencyTo, $url, $response)
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
                    (double)$response['rates'][$currencyFrom]
                );
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function _convert($currencyFrom, $currencyTo)
    {
        return 1;
    }
}