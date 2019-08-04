<?php declare(strict_types=1);

namespace Sozo\CurrencyConversionExtended\Test\Unit\Model\Currency\Import;

use Magento\Directory\Model\Currency;
use Sozo\CurrencyConversionExtended\Model\Currency\Import\CurrencyLayer;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * OpenRates Test
 */
class CurrencyLayerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CurrencyLayer
     */
    private $model;

    /**
     * @var CurrencyFactory|MockObject
     */
    private $currencyFactory;

    /**
     * @var ZendClientFactory|MockObject
     */
    private $httpClientFactory;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->currencyFactory = $this->getMockBuilder(CurrencyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->httpClientFactory = $this->getMockBuilder(ZendClientFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->model = new CurrencyLayer($this->currencyFactory, $this->scopeConfig, $this->httpClientFactory);
    }

    /**
     * Test Fetch Rates
     *
     * @return void
     */
    public function testFetchRatesSuccess(): void
    {
        $currencyFromList = ['USD'];
        $currencyToList = ['EUR', 'GBP'];
        $responseBody = '{"success":true,"terms":"https:\/\/currencylayer.com\/terms","privacy":"https:\/\/currencylayer.com\/privacy","timestamp":1564934885,"source":"USD","quotes":{"USDEUR":0.89851,"USDGBP":0.822435}}';
        $expectedCurrencyRateList = ['USD' => ['EUR' => 0.89851, 'GBP' => 0.822435]];
        $message = "We can't retrieve a rate from "
            . "http://apilayer.net/api/live?access_key={{api_key}}&currencies=EUR,GBP&source=USD&format=1";

        $this->scopeConfig->method('getValue')
            ->withConsecutive(
                ['currency/open_rates/api_key', 'store'],
                ['currency/open_rates/timeout', 'store']
            )
            ->willReturnOnConsecutiveCalls('api_key', 100);

        /** @var Currency|MockObject $currency */
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var ZendClient|MockObject $httpClient */
        $httpClient = $this->getMockBuilder(ZendClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var DataObject|MockObject $currencyMock */
        $httpResponse = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBody'])
            ->getMock();

        $this->currencyFactory->method('create')
            ->willReturn($currency);
        $currency->method('getConfigBaseCurrencies')
            ->willReturn($currencyFromList);
        $currency->method('getConfigAllowCurrencies')
            ->willReturn($currencyToList);

        $this->httpClientFactory->method('create')
            ->willReturn($httpClient);
        $httpClient->method('setUri')
            ->willReturnSelf();
        $httpClient->method('setConfig')
            ->willReturnSelf();
        $httpClient->method('request')
            ->willReturn($httpResponse);
        $httpResponse->method('getBody')
            ->willReturn($responseBody);

        self::assertEquals($expectedCurrencyRateList, $this->model->fetchRates());
    }

    /**
     * Test Fetch Rates
     *
     * @return void
     */
    public function testFetchRatesFail(): void
    {
        $currencyFromList = ['GBP'];
        $currencyToList = ['BTC'];
        $responseBody = '{"error":"Symbols \'BTC\' are invalid for date 2019-07-31."}';
        $expectedCurrencyRateList = ['error' => "Symbols 'BTC' are invalid for date 2019-07-31."];
        $message = "Symbols 'BTC' are invalid for date 2019-07-31.";

        $this->scopeConfig->method('getValue')
            ->withConsecutive(
                ['currency/open_rates/api_key', 'store'],
                ['currency/open_rates/timeout', 'store']
            )
            ->willReturnOnConsecutiveCalls('api_key', 100);

        /** @var Currency|MockObject $currency */
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var ZendClient|MockObject $httpClient */
        $httpClient = $this->getMockBuilder(ZendClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var DataObject|MockObject $currencyMock */
        $httpResponse = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBody'])
            ->getMock();

        $this->currencyFactory->method('create')
            ->willReturn($currency);
        $currency->method('getConfigBaseCurrencies')
            ->willReturn($currencyFromList);
        $currency->method('getConfigAllowCurrencies')
            ->willReturn($currencyToList);

        $this->httpClientFactory->method('create')
            ->willReturn($httpClient);
        $httpClient->method('setUri')
            ->willReturnSelf();
        $httpClient->method('setConfig')
            ->willReturnSelf();
        $httpClient->method('request')
            ->willReturn($httpResponse);
        $httpResponse->method('getBody')
            ->willReturn($responseBody);

        $this->model->fetchRates();

        $messages = $this->model->getMessages();
        self::assertNotEmpty($messages);
        self::assertTrue(is_array($messages));
        self::assertEquals($message, (string)$messages[0]);
    }
}
