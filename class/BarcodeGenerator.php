<?php

namespace StudioDemmys\barcode;

use StudioDemmys\barcode\type\BarcodeParameter;
use StudioDemmys\barcode\type\ErrorLevel;
use StudioDemmys\barcode\type\ImageFormat;

class BarcodeGenerator
{
    public ?BarcodeParameter $barcode_parameter = null;
    public ?string $output = null;
    public ?string $content_type = null;
    public ?string $content_disposition = null;
    
    public function __construct(array $user_input)
    {
        $this->barcode_parameter = new BarcodeParameter($user_input);
        $barcode = BarcodeJan13::Instantiate($this->barcode_parameter);
        switch ($this->barcode_parameter->format) {
            case ImageFormat::Png:
                $this->output = $barcode->getPng();
                $this->content_type = 'image/png';
                
                $filename = str_replace('%code%', $this->barcode_parameter->code,
                    Config::getConfigOrSetIfUndefined('output/png/filename', 'JAN13_%code%.png'));
                break;
            case ImageFormat::Svg:
                $this->output = $barcode->getSvg();
                $this->content_type = 'image/svg+xml';
                $filename = str_replace('%code%', $this->barcode_parameter->code,
                    Config::getConfigOrSetIfUndefined('output/svg/filename', 'JAN13_%code%.svg'));
                break;
            case ImageFormat::Eps:
                $this->output = $barcode->getEps();
                $this->content_type = 'application/postscript';
                $filename = str_replace('%code%', $this->barcode_parameter->code,
                    Config::getConfigOrSetIfUndefined('output/eps/filename', 'JAN13_%code%.eps'));
                break;
            default:
                throw new Exception(ErrorLevel::Error, "E_IMAGE_FORMAT_UNSUPPORTED",
                    'The requested format is "' . $this->barcode_parameter->format->value . '"');
                break;
        }
        $this->content_disposition = ($this->barcode_parameter->download ? 'attachment' : 'inline') .
            '; filename="' . $filename . '"';
    }
    
    public function outputImage() : void
    {
        header('Content-Type: ' . $this->content_type);
        header('Content-Disposition: ' . $this->content_disposition);
        header('Content-Length:' . strlen($this->output));
        echo $this->output;
    }
}