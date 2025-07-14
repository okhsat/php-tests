<?php
header('Content-Type: application/json; charset=utf-8');

$schemes = [   
    '6_months' => ['months' => 6, 'interest' => 0.05],   // +5% total   
    '12_months' => ['months' => 12, 'interest' => 0.10], // +10% total   
    '24_months' => ['months' => 24, 'interest' => 0.20]  // +20% total
];
$productsData = json_decode(file_get_contents('../data/products.json', true));
$products = [];

if ( count($productsData) > 0 ) {
    foreach ( $productsData as $p ) {
        $products[(int) $p->id] = $p;
    }
}

function jsonResponse($code = 200, $message = '', $data = [])
{
    $response = [
        'status' => true,
        'message' => $message,
        'data' => $data
    ];

    if ( ! in_array($code, [200, 201, 202]) ) {
        $response['status'] = false;

    } else {
        $response = $data;
    }

    http_response_code($code);
    echo json_encode($response);
    die();
}

function getPath() {
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $url_parts = parse_url($url);
    $path = $url_parts['path'];
    $path = trim($path, '/ ');

    return $path;
}

function validateRequest() 
{
    if ( $_SERVER['CONTENT_TYPE'] === 'application/json' ) {
        $data = json_decode(file_get_contents('php://input'), true);
        
    } else {    
        jsonResponse(400, 'Invalid Request');
    }
    
    if ( ! isset($data['name']) || empty($data['name']) || ! preg_match("/^[a-zA-Z-' ]*$/", $data['name']) ) {
        jsonResponse(400, 'Invalid Name');
    }    
    
    if ( ! isset($data['email']) || empty($data['email']) || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL) ) {
        jsonResponse(400, 'Invalid Email');
    }
    
    if ( ! isset($data['products']) || empty($data['products']) || ! is_array($data['products']) || count($data['products']) < 1 ) {
        jsonResponse(400, 'Invalid Products');
    }

    if ( ! isset($data['scheme']) || empty($data['scheme']) || ! in_array($data['scheme'], array_keys($GLOBALS['schemes'])) ) {
        jsonResponse(400, 'Invalid Scheme');
    }
    
    return $data;
}

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( ! preg_match("/^applications$/", getPath()) ) {
        jsonResponse(404, 'Not Found');
    }

    $data = validateRequest();

    jsonResponse(201, 'Success', $data + [
        'application_id' => 1,
        'total_price' => 1200.00,
        'monthly_payment' => 100.00
    ]);

} else {
    if ( ! preg_match("/^applications\/([0-9]+)$/", getPath(), $matches) ) {
        jsonResponse(404, 'Not Found');
    }
    
    $id = (int) $matches[1];

    if ( isset($products[$id]) ) {
        jsonResponse(200, 'Success', $products[$id]);
    }

    jsonResponse(404, 'Not Found');
}