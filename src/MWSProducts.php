<?php

namespace Looxis\LaravelAmazonMWS;

use Looxis\LaravelAmazonMWS\MWSClient;

class MWSProducts
{
    const VERSION = '2011-10-01';

    protected $client;

    public function __construct(MWSClient $client)
    {
        $this->client = $client;
    }

    public function get($ids)
    {
        $ids = is_array($ids) ? $ids : func_get_args();
        $params = [];

        foreach ($ids as $key => $id) {
            $keyName = 'IdList.Id.'.($key + 1);
            $params[$keyName] = $id;
        }

        $params['IdType'] = 'ASIN';
        $params['MarketplaceId'] = 'A1F83G8C2ARO7P';

        $response = $this->client->post('GetMatchingProductForId', '/Products/'.self::VERSION, self::VERSION, $params);

        $dataName = 'Products.Product';
        if (count($ids) > 1) {
            $dataName = '*.Products.Product';
        }

        return $this->parseResponse($response, 'GetMatchingProductForIdResult', $dataName);
    }

    public function getMyPrice($ids)
    {
        $ids = is_array($ids) ? $ids : func_get_args();
        $params = [];

        foreach ($ids as $key => $id) {
            $keyName = 'ASINList.ASIN.'.($key + 1);
            $params[$keyName] = $id;
        }

        $params['MarketplaceId'] = 'A1F83G8C2ARO7P';

        $response = $this->client->post('GetMyPriceForASIN', '/Products/'.self::VERSION, self::VERSION, $params);

        $dataName = '*.Product.Offers';

        return $this->parseResponse($response, 'GetMyPriceForASINResult', $dataName);
    }

    public function parseResponse($response, $resultTypeName, $dataName)
    {
        $requestId = data_get($response, 'ResponseMetadata.RequestId');
        $data = data_get($response, $resultTypeName.'.'.$dataName);
        $nextToken = data_get($response, $resultTypeName.'.NextToken');
        $createdBefore = data_get($response, $resultTypeName.'.CreatedBefore');

        //Check if single list item and wrap
        if (! is_null($data) && (! data_get($data, '0')) && in_array($resultTypeName, [
            'GetProductResult',
        ])) {
            $data = [$data];
        }

        $data = [
            'request_id' => $requestId,
            'data' => $data,
        ];

        if ($nextToken) {
            $data['next_token'] = $nextToken;
        }

        if ($createdBefore) {
            $data['created_before'] = $createdBefore;
        }

        if ($resultTypeName == 'ListOrderItemsResult') {
            $data['order_id'] = data_get($response, $resultTypeName.'.AmazonOrderId');
        }

        return $data;
    }
}
