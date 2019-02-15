<?php

namespace Sozo\CurrencyConversionExtended\Model\Currency\Import;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\Currency\Import\AbstractImport;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Store\Model\ScopeInterface;
use Sozo\ProductFeatures\Logger\SozoLogger;

/**
 * Currency rate import model (From https://free.currencyconverterapi.com/)
 */
class FreeCurrencyConverterApi extends AbstractImport
{
    /**
     * @var string
     */
    const CURRENCY_CONVERTER_URL = 'https://free.currencyconverterapi.com/api/v6/convert?apiKey={{API_KEY}}&q={{CURRENCY_FROM}}_{{CURRENCY_TO}}&compact=ultra';

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

    protected $accessKey;

    protected $logger;

    /**
     * Initialize dependencies
     *
     * @param CurrencyFactory $currencyFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ZendClientFactory $httpClientFactory
     * @param SozoLogger $logger
     */
    public function __construct(
        CurrencyFactory $currencyFactory,
        ScopeConfigInterface $scopeConfig,
        ZendClientFactory $httpClientFactory,
        SozoLogger $logger
    )
    {
        parent::__construct($currencyFactory);
        $this->scopeConfig = $scopeConfig;
        $this->httpClientFactory = $httpClientFactory;
        $this->logger = $logger;

        $this->accessKey = $this->scopeConfig->getValue(
            'currency/currency_converter/api_key',
            ScopeInterface::SCOPE_STORE
        );

        $this->logger->addDebug('Model loaded');
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
                $url = str_replace('{{API_KEY}}', $this->accessKey, self::CURRENCY_CONVERTER_URL);
                $url = str_replace('{{CURRENCY_FROM}}', $currencyFrom, $url);
                $url = str_replace('{{CURRENCY_TO}}', $to, $url);
                $response = $this->getServiceResponse($url);
                if ($currencyFrom == $to) {
                    $data[$currencyFrom][$to] = $this->_numberFormat(1);
                } else {
                    if (empty($response)) {
                        $this->_messages[] = __('We can\'t retrieve a rate from %1 for %2.', $url, $to);
                        $data[$currencyFrom][$to] = null;
                    } else {
                        $data[$currencyFrom][$to] = $this->_numberFormat(
                            (double)$response[$currencyFrom . '_' . $to]
                        );
                    }
                }
            } finally {
                ini_restore('max_execution_time');
            }
        }
        return $data;
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
     * {@inheritdoc}
     */
    protected function _convert($currencyFrom, $currencyTo)
    {
        return 1;
    }
}