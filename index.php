<?php
namespace StudioDemmys\barcode;

require_once dirname(__FILE__)."/initialize.php";

Logging::info("Barcode generation start");

try {
    $barcode_data = new BarcodeGenerator($_GET);
} catch (\Exception $e) {
    Logging::error("Barcode generation failed!");
    $http_kit = new HttpKit();
    $http_kit->sendBadRequest(true, "Invalid parameters");
    throw new Exception(type\ErrorLevel::Error, "E_BARCODE_GENERATION_FAILURE", $e->getMessage(), $e);
}

Logging::info("Barcode generated -- " . print_r($barcode_data->barcode_parameter, true));

$barcode_data->outputImage();

