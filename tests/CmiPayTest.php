<?php

namespace Tests;

use ReflectionMethod;
use CmiPayBundle\CmiPay;
use PHPUnit\Framework\TestCase;
use CmiPayBundle\Controller\CmiPayController;

/**
 * Class CmiPayTest
 * @package Tests
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class CmiPayTest extends TestCase
{
    /**
     * @var \CmiPayBundle\CmiPay
     */
    private $cmiPay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cmiPay = (new CmiPay())
            ->setGatewayurl('https://testpayment.cmi.co.ma/fim/est3Dgate')
            ->setclientid('600000000')
            ->setTel('05000000')
            ->setEmail('email@domaine.ma')
            ->setBillToName('BillToName')
            ->setBillToCompany('BillToCompany')
            ->setBillToStreet1('BillToStreet1')
            ->setBillToStateProv('BillToStateProv')
            ->setBillToPostalCode('BillToPostalCode')
            ->setBillToCity('BillToCity')
            ->setBillToCountry('MA')
            ->setOid('12345ABCD')
            ->setCurrency('504')
            ->setAmount('31.50')
            ->setOkUrl('http://okUrl')
            ->setCallbackUrl('http://callbackUrl')
            ->setFailUrl('http://okFailUrl')
            ->setShopurl('http://shopUrl')
            ->setEncoding('UTF-8')
            ->setStoretype('3D_PAY_HOSTING')
            ->setHashAlgorithm('ver3')
            ->setTranType('PreAuth')
            ->setRefreshtime('5')
            ->setLang('fr')
            ->setRnd('0.74494600 1608498557')
        ;
    }

    /**
     * @test
     */
    public function CmiPay_toArray_is_similar_to_convertData()
    {
        $expected = [
            'gatewayurl' => 'https://testpayment.cmi.co.ma/fim/est3Dgate',
            'secretKey' => '',
            'clientid' => '600000000',
            'tel' => '05000000',
            'email' => 'email@domaine.ma',
            'billToName' => 'BillToName',
            'billToCompany' => 'BillToCompany',
            'billToStreet1' => 'BillToStreet1',
            'billToStateProv' => 'BillToStateProv',
            'billToPostalCode' => 'BillToPostalCode',
            'billToCity' => 'BillToCity',
            'billToCountry' => 'MA',
            'oid' => '12345ABCD',
            'currency' => '504',
            'amount' => '31.50',
            'okUrl' => 'http://okUrl',
            'callbackUrl' => 'http://callbackUrl',
            'failUrl' => 'http://okFailUrl',
            'shopurl' => 'http://shopUrl',
            'encoding' => 'UTF-8',
            'storetype' => '3D_PAY_HOSTING',
            'tranType' => 'PreAuth',
            'refreshtime' => '5',
            'hashAlgorithm' => 'ver3',
            'lang' => 'fr',
            'rnd' => '0.74494600 1608498557',
        ];

        $reflectionMethod = new ReflectionMethod(CmiPayController::class, 'convertData');
        $reflectionMethod->setAccessible(true);

        $data = $reflectionMethod->invoke(new CmiPayController(), $this->cmiPay);

        $this->assertEquals($expected, $data);
        $this->assertEquals($expected, $this->cmiPay->toArray());
        $this->assertEquals($data, $this->cmiPay->toArray());
    }

    /**
     * @test
     */
    public function hash_cmpPay_data()
    {
        $reflectionMethod = new ReflectionMethod(CmiPayController::class, 'hashValue');
        $reflectionMethod->setAccessible(true);

        $data = $this->cmiPay->toArray();
        $hashValue = $reflectionMethod->invoke(new CmiPayController(), $data);

        $this->assertSame(
            'VppibbLUs0SCB/ZgtUIKfT35ikqAKG6zYIazumAiUIt06KLG9TQ70vfJQoOAcKpbfkWEf4LZ6ZirEHjyWSUN9w==',
            $hashValue
        );
    }
}
