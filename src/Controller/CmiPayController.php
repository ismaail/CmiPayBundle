<?php

namespace CmiPayBundle\Controller;

use CmiPayBundle\CmiPay;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Class CmiPayController
 * @package CmiPayBundle\Controller
 */
class CmiPayController extends AbstractController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function requestPay(Request $request)
    {
        $cmiPay = new CmiPay();
        // Setup new payment parameters
        $okUrl = $this->generateUrl('cmi_pay_okFail', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $shopUrl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        $failUrl = $this->generateUrl('cmi_pay_okFail', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $callbackUrl = $this->generateUrl('cmi_pay_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $rnd = microtime();
        $cmiPay->setGatewayurl('https://testpayment.cmi.co.ma/fim/est3Dgate')
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
            ->setOkUrl($okUrl)
            ->setCallbackUrl($callbackUrl)
            ->setFailUrl($failUrl)
            ->setShopurl($shopUrl)
            ->setEncoding('UTF-8')
            ->setStoretype('3D_PAY_HOSTING')
            ->setHashAlgorithm('ver3')
            ->setTranType('PreAuth')
            ->setRefreshtime('5')
            ->setLang('fr')
            ->setRnd($rnd)
        ;

        $data = $this->convertData($cmiPay);
        $hash = $this->hashValue($data);
        $data['HASH'] = $hash;
        $data = $this->unsetData($data);

        return $this->render('@CmiPay/payrequest.html.twig', [
            'data' => $data,
            'url' => $cmiPay->getGatewayurl(),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function okFail(Request $request)
    {
        $postData = $request->request->all();

        if ($postData) {
            $actualHash = $this->hashValue($postData);
            $retrievedHash = $postData['HASH'];

            if ($retrievedHash === $actualHash && $postData['ProcReturnCode'] === '00') {
                $response = 'HASH is successfull';
            } else {
                $response = 'Security Alert. The digital signature is not valid';
            }
        } else {
            $response = 'No Data POST';
        }

        return $this->render('@CmiPay/okFail.html.twig', [
            'response' => $response,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callback(Request $request)
    {
        $postData = $request->request->all();

        if ($postData) {
            $actualHash = $this->hashValue($postData);
            $retrievedHash = $postData['HASH'];

            if ($retrievedHash === $actualHash && '00' === $request->request->get('ProcReturnCode')) {
                $response = 'ACTION=POSTAUTH';
            } else {
                $response = 'FAILURE';
            }
        } else {
            $response = 'No Data POST';
        }

        return $this->render('@CmiPay/callback.html.twig', [
            'response' => $response,
        ]);
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function hashValue(array $data): string
    {
        $data = $this->unsetData($data);

        $storeKey = 'TEST1234';
        $postParams = array_keys($data);

        natcasesort($postParams);

        $hashval = '';

        foreach ($postParams as $param) {
            $paramValue = trim(html_entity_decode(preg_replace("/\n$/", '', $data[$param]), ENT_QUOTES, 'UTF-8'));
            $escapedParamValue = str_replace(["\\", "|"], ["\\\\", "\\|"], $paramValue);
            $escapedParamValue = preg_replace('/document(.)/i', 'document.', $escapedParamValue);

            $lowerParam = strtolower($param);

            if ('hash' !== $lowerParam && 'encoding' !== $lowerParam) {
                $hashval .= ($escapedParamValue . '|');
            }
        }

        $escapedStoreKey = str_replace(["\\", "|"], ["\\\\", "\\|"], $storeKey);
        $hashval .= $escapedStoreKey;

        $calculatedHashValue = hash('sha512', $hashval);
        return base64_encode(pack('H*', $calculatedHashValue));
    }

    /**
     * @param \CmiPayBundle\CmiPay $cmiPay
     *
     * @return array
     *
     * @deprecated
     */
    private function convertData(CmiPay $cmiPay)
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($cmiPay, 'json');
        $data = json_decode($jsonContent, true);

        foreach ($data as $key => $value) {
            $data[$key] = trim(html_entity_decode($value));
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function unsetData(array $data): array
    {
        unset($data['gatewayurl'], $data['secretKey']);

        return $data;
    }
}
