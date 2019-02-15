<?php

namespace Sozo\CurrencyConversionExtended\Model\Currency\Import;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\Currency\Import\AbstractImport;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Store\Model\ScopeInterface;

/**
 * Currency rate import model (From https://free.currencyconverterapi.com/)
 */
class CurrencyConverter extends AbstractImport
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
     * @param string $currencyFrom
     * @param string $currencyTo
     * @param int $retry
     * @return float|null
     */
    protected function _convert($currencyFrom, $currencyTo, $retry = 0)
    {
        $result = null;
        $timeout = (int)$this->scopeConfig->getValue(
            'currency/google/timeout',
            ScopeInterface::SCOPE_STORE
        );

        $accessKey = $this->scopeConfig->getValue(
            'currency/currency_converter/api_key',
            ScopeInterface::SCOPE_STORE
        );

        $key = $currencyFrom . '_' . $currencyTo;

        $url = str_replace('{{CURRENCY_FROM}}', $currencyFrom, self::CURRENCY_CONVERTER_URL);
        $url = str_replace('{{CURRENCY_TO}}', $currencyTo, $url);
        $url = str_replace('{{API_KEY}}', $accessKey, $url);

        /** @var \Magento\Framework\HTTP\ZendClient $httpClient */
        $httpClient = $this->httpClientFactory->create();

        try {
            $response = $httpClient->setUri($url)
                ->setConfig(['timeout' => $timeout])
                ->request('GET')
                ->getBody();

            $data = json_decode($response, true);
            if (isset($data[$key])) {
                $result = (float)$data[$key];
            } else {
                $this->_messages[] = __('We can\'t retrieve a rate from %1.', $url);
                $this->_messages[] = __('Key %1', $key);
            }
        } catch (\Exception $e) {
            if ($retry == 0) {
                $this->_convert($currencyFrom, $currencyTo, 1);
            } else {
                $this->_messages[] = __('We can\'t retrieve a rate from %1.', $url);
            }
        }
        return $result;
    }
}