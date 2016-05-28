<?php

namespace ShoppinPal\Vend\Api\V0;

use ShoppinPal\Vend\Api\BaseApiAbstract;
use ShoppinPal\Vend\DataObject\Entity\V0\RegisterSale;
use YapepBase\Communication\CurlHttpRequest;
use YapepBase\Exception\ParameterException;

class RegisterSales extends BaseApiAbstract
{
    public function create(RegisterSale $registerSale)
    {
        if ($registerSale->returnFor) {
            return $this->createReturn($registerSale);
        }

        return $this->doCreate($registerSale);
    }

    protected function doCreate(RegisterSale $registerSale)
    {
        $request = $this->getAuthenticatedRequestForUri('api/register_sales');
        $request->setMethod(CurlHttpRequest::METHOD_POST);

        $request->setPayload(json_encode($registerSale->toUnderscoredArray([], true)), CurlHttpRequest::PAYLOAD_TYPE_RAW);

        $result = $this->sendRequest($request);

        return new RegisterSale($result['register_sale'], RegisterSale::UNKNOWN_PROPERTY_IGNORE);
    }

    public function createReturn(RegisterSale $registerSale)
    {
        if (empty($registerSale->returnFor)) {
            throw new ParameterException('No returnFor set in sale');
        }

        // We need to do an empty PUT request for the original sale to the V2 return endpoint, then modify it via V0.
        $returnApiUrl = '/api/2.0/sales/' . urlencode($registerSale->returnFor) . '/actions/return';
        $request      = $this->getAuthenticatedRequestForUri($returnApiUrl);

        $request->setMethod(CurlHttpRequest::METHOD_PUT);

        $returnResult = $this->sendRequest($request);

        $modifiedRegisterSale = clone($registerSale);

        $modifiedRegisterSale->id = $returnResult['data']['id'];

        return $this->doCreate($modifiedRegisterSale);
    }
}
