<?php
/**
 * carfify – API endpoint vehicles
 * 
 * Returns a list of supported vehicles. This file is optimized
 * for Vercel’s PHP Runtime (see “vercel-php@0.6.0” in vercel.json).
 * 
 * @param Psr\Http\Message\ServerRequestInterface $request  The incoming request
 * @return mixed                                    Either Symfony Response or JSON
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

return function (Request $request) {
    // Build response data
    $vehicles = [
        [
            'id'     => 1,
            'brand'  => 'VW',
            'model'  => 'Golf',
            'year'   => 2017,
            'engine' => ['petrol', 'TFSI', '1.4', '92 kW'],
        ],
        [
            'id'     => 2,
            'brand'  => 'BMW',
            'model'  => '320i',
            'year'   => 2019,
            'engine' => ['petrol', 'B48', '2.0', '135 kW'],
        ],
        [
            'id'     => 3,
            'brand'  => 'Tesla',
            'model'  => 'Model 3',
            'year'   => 2021,
            'engine' => ['electric', 'RWD', '60 kWh', '211 kW'],
        ],
    ];

    $response = new JsonResponse(
        $vehicles,
        Response::HTTP_OK,
        [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods'   => 'GET, OPTIONS',
            'Access-Control-Allow-Headers'   => 'Content-Type, Authorization',
            'Content-Type'                   => 'application/json; charset=utf-8',
            'Cache-Control'                 => 'public, max-age=3600',
        ]
    );

    // Handle pre-flight CORS
    if ($request->isMethod('OPTIONS')) {
        return $response->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    return $response;
};
