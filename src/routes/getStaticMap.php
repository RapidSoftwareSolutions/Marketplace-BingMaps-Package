<?php

$app->post('/api/BingMaps/getStaticMap', function ($request, $response) {

    $settings = $this->settings;
    $checkRequest = $this->validation;
    $validateRes = $checkRequest->validate($request, ['key','query','mapLayer']);

    if(!empty($validateRes) && isset($validateRes['callback']) && $validateRes['callback']=='error') {
        return $response->withHeader('Content-type', 'application/json')->withStatus(200)->withJson($validateRes);
    } else {
        $post_data = $validateRes;
    }

    $requiredParams = ['key'=>'key','centerPoint'=>'centerPoint', 'query'=>'query', 'mapLayer'=>'mapLayer'];
    $optionalParams = [];
    $bodyParams = [
       'query' => ['key','mapLayer']
    ];

    $data = \Models\Params::createParams($requiredParams, $optionalParams, $post_data['args']);

    $client = $this->httpClient;
    $query_str = "https://dev.virtualearth.net/REST/V1/Imagery/Map/Road/{$data['query']}";


    $requestParams = \Models\Params::createRequestBody($data, $bodyParams);
    $requestParams['headers'] = [];

    try {
        $resp = $client->get($query_str, $requestParams);
        $responseBody = $resp->getBody()->getContents();

        if ($resp->getStatusCode() == 200) {
            $size = $resp->getHeader('Content-Length')[0];
            $uploadServiceResponse = $client->post($settings['uploadServiceUrl'], [
                'multipart' => [
                    [
                        'name' => 'length',
                        'contents' => $size
                    ],
                    [
                        "name" => "file",
                        "filename" => "map.jpeg",
                        "contents" => $responseBody
                    ]

                ]
            ]);
            $uploadServiceResponseBody = $uploadServiceResponse->getBody()->getContents();
            if ($uploadServiceResponse->getStatusCode() == 200) {
                $result['callback'] = 'success';
                $result['contextWrites']['to'] = json_decode($uploadServiceResponse->getBody());
            }
            else {
                $result['callback'] = 'error';
                $result['contextWrites']['to']['status_code'] = 'API_ERROR';
                $result['contextWrites']['to']['status_msg'] = is_array($uploadServiceResponseBody) ? $uploadServiceResponseBody : json_decode($uploadServiceResponseBody);
            }
        } else {
            $result['callback'] = 'error';
            $result['contextWrites']['to']['status_code'] = 'API_ERROR';
            $result['contextWrites']['to']['status_msg'] = is_array($responseBody) ? $responseBody : json_decode($responseBody);
        }
    } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
        $result['callback'] = 'error';
        $result['contextWrites']['to']['status_code'] = 'API_ERROR';
        $result['contextWrites']['to']['status_msg'] = json_decode($exception->getResponse()->getBody());
    }
    return $response->withHeader('Content-type', 'application/json')->withStatus(200)->withJson($result);
});