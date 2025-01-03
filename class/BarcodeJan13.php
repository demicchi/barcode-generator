<?php

namespace StudioDemmys\barcode;


use StudioDemmys\barcode\type\BarcodeParameter;
use StudioDemmys\barcode\type\Color;
use StudioDemmys\barcode\type\ErrorLevel;

class BarcodeJan13 extends \Picqer\Barcode\BarcodeGenerator
{
    protected \Imagick $imagick;
    protected string $svg_body;
    protected string $eps_body;
    protected int $width;
    protected int $height;
    protected string $code;
    
    public function __construct(string $code, int $height, bool $numbered, int $margin = 0, int $width_factor = 2,
                                ?Color $foreground = null, ?Color $background = null)
    {
        $separator_bar_position = array_map(fn($v): int => $v * $width_factor, [0, 2, 46, 48, 92, 94]);
        
        if (is_null($foreground)) {
            $foreground = new Color([0, 0, 0, 1.0]);
        }
        Logging::debug("Foreground color: " . $foreground->getCssRgbaText());
        
        if (is_null($background)) {
            $background = new Color([255, 255, 255, 1.0]);
        }
        Logging::debug("Background color: " . $background->getCssRgbaText());
        
        $barcode = $this->getBarcodeData($code, self::TYPE_EAN_13);
        $this->code = $barcode->getBarcode();
        Logging::debug("Input code = {$code}, Calculated code = " . $this->code);
        
        $width = ($barcode->getWidth() * $width_factor) + ($margin * 2);
        Logging::debug("Barcode width is {$width}");
        
        $left_space = 0;
        
        if ($numbered) {
            $left_space = 10 * $width_factor;
            $width += $left_space;
            Logging::debug("Entire width is {$width}");
        }
        
        $this->width = $width;
        $this->height = $height;
        
        $svg = "";
        $eps = "gsave" . PHP_EOL;
        if ($background->a > 0) {
            $svg .= "\t" . '<g id="background" fill="' . $background->getCssRgbaText() . '" stroke="none">' . PHP_EOL;
            $svg .= "\t\t" . '<rect x="0" y="0" width="' . $width . '" height="' . $height . '" />' . PHP_EOL;
            $svg .= "\t</g>" . PHP_EOL;
            
            $eps .= $background->getEpsRgbText() ." setrgbcolor". PHP_EOL;
            $eps .= "0 0 {$width} {$height} rectfill" . PHP_EOL;
        }
        
        $svg .= "\t" . '<g id="bars" fill="' . $foreground->getCssRgbaText() . '" stroke="none">' . PHP_EOL;
        $eps .= $foreground->getEpsRgbText() ." setrgbcolor". PHP_EOL;
        
        $position_horizontal = 0;
        
        /** @var \Picqer\Barcode\BarcodeBar $bar */
        foreach ($barcode->getBars() as $bar) {
            $bar_width = $bar->getWidth() * $width_factor;
            if ($bar->isBar() && $bar_width > 0) {
                $bar_height = $height - ($margin * 2);
                if ($numbered) {
                    try {
                        $bar_height -= ((Config::getConfig('svg/character-size'))[1] + 2) * $width_factor;
                        if (in_array($position_horizontal, $separator_bar_position, true)) {
                            $bar_height += ((Config::getConfig('svg/character-size'))[1] + 2) / 2 * $width_factor;
                        }
                    } catch (\Exception $e) {
                        throw new Exception(ErrorLevel::Error, "E_BAR_HEIGHT_CALCULATION", $e->getMessage(), $e);
                    }
                }
                $svg .= "\t\t" . '<rect x="' . $margin + $left_space + $position_horizontal . '" y="' . $margin . '" width="' . $bar_width . '" height="' . $bar_height . '"  stroke-width="0" />' . PHP_EOL;
                $eps .= $margin + $left_space + $position_horizontal . ' ' . $height - $margin - $bar_height . ' ' . $bar_width . ' ' . $bar_height . ' rectfill' . PHP_EOL;
            }
            $position_horizontal += $bar_width;
        }
        
        $svg .= "\t</g>" . PHP_EOL;
        $eps .= "grestore" . PHP_EOL;
        
        if ($numbered) {
            try {
                $character_width = (Config::getConfig('svg/character-size'))[0] * $width_factor;
                $svg .= $this->getSvgCharacterPath($this->code[0], 0, $height - 1, $margin,
                    $width_factor, $foreground);
                $eps .= $this->getEpsCharacterPath($this->code[0], 0, $height - 1, $margin,
                    $width_factor, $foreground);
                for ($i = 1; $i <= 6; $i++) {
                    $svg .= $this->getSvgCharacterPath($this->code[$i], (14 * $width_factor) + ($character_width * ($i - 1)),
                        $height - 1, $margin, $width_factor, $foreground);
                    $eps .= $this->getEpsCharacterPath($this->code[$i], (14 * $width_factor) + ($character_width * ($i - 1)),
                        $height - 1, $margin, $width_factor, $foreground);
                }
                for ($i = 7; $i <= 12; $i++) {
                    $svg .= $this->getSvgCharacterPath($this->code[$i], (60 * $width_factor) + ($character_width * ($i - 7)),
                        $height - 1, $margin, $width_factor, $foreground);
                    $eps .= $this->getEpsCharacterPath($this->code[$i], (60 * $width_factor) + ($character_width * ($i - 7)),
                        $height - 1, $margin, $width_factor, $foreground);
                }
            } catch (\Exception $e) {
                throw new Exception(ErrorLevel::Error, "E_NUMBER_PATH_DRAWING_FAILURE", $e->getMessage(), $e);
            }
        }
        
        $this->svg_body = $svg;
        $this->eps_body = $eps;
        
        try {
            $this->imagick = new \Imagick();
            $this->imagick->setBackgroundColor(new \ImagickPixel('none'));
            if (Config::getConfigOrSetIfUndefined("imagick/svg-workaround", true)) {
                // MSVG renders vectors 1px wider then the actual size.
                // As a workaround, scale the svg 20 times larger, then shrink to the original size.
                $scale_factor = 20 / $width_factor;
                $this->imagick->readImageBlob($this->getScaledSvg($svg, $width, $height, $scale_factor, $scale_factor));
                $this->imagick->scaleImage($width, $height);
            } else {
                $this->imagick->readImageBlob($this->getScaledSvg($svg, $width, $height));
            }
        } catch (\Exception $e) {
            throw new Exception(ErrorLevel::Error, "E_IMAGICK_GENERATION", $e->getMessage(), $e);
        }
    }
    
    public function getPng() : string
    {
        try {
            $this->imagick->setImageFormat("png");
            return $this->imagick->getImageBlob();
        } catch (\Exception $e) {
            throw new Exception(ErrorLevel::Error, "E_IMAGICK_CONVERSION",
                "Image format is PNG -- " . $e->getMessage(), $e);
        }
    }
    
    public function getSvg() : string
    {
        return $this->getScaledSvg($this->svg_body, $this->width, $this->height);
    }
    
    public function getEps() : string
    {
        $eps = '%!PS-Adobe-3.0 EPSF-3.0' . PHP_EOL;
        $eps .= '%%BoundingBox: 0 0 ' . $this->width . ' ' . $this->height . PHP_EOL;
        $eps .= PHP_EOL;
        $eps .= $this->eps_body . PHP_EOL;
        
        return $eps;
    }
    
    public function getCode() : string
    {
        return $this->code;
    }
    
    public function getSvgTransformMatrix(array $svg_matrix, float $x = 0, float $y = 0,
                                          float $scale_factor_w = 1, float $scale_factor_h = 1) : string
    {
        $svg_matrix[0] *= $scale_factor_w;
        $svg_matrix[3] *= $scale_factor_h;
        $svg_matrix[4] += $x;
        $svg_matrix[5] += $y;
        
        return 'matrix(' . implode(',', array_map(fn($v): string => sprintf('%01.3f', $v), $svg_matrix)) . ')';
    }
    
    public function getEpsCoordinateMatrix(array $svg_matrix, float $y_bottom, float $x = 0, float $y = 0,
                                          float $scale_factor_w = 1, float $scale_factor_h = 1) : string
    {
        $svg_matrix[0] *= $scale_factor_w;
        $svg_matrix[3] *= -1 * $scale_factor_h;
        $svg_matrix[4] += $x;
        $svg_matrix[5] = $y_bottom + 1 - $svg_matrix[5] - $y;
        
        return '[' . implode(' ', array_map(fn($v): string => sprintf('%01.3f', $v), $svg_matrix)) . '] concat';
    }
    
    public function getSvgCharacterPath(string $code_character, float $x_offset, float $y_bottom, int $margin,
                                        float $scale_factor = 1, ?Color $foreground = null) : string
    {
        if (is_null($foreground)) {
            $foreground = new Color([0, 0, 0, 1.0]);
        }
        
        try {
            $character_matrix = Config::getConfig('svg/character/' . $code_character . '/matrix');
            $character_path = Config::getConfig('svg/character/' . $code_character . '/path');
            $character_fill_rule = Config::getConfig('svg/character/' . $code_character . '/fill-rule');
            
            $svg = "\t" . '<g transform="' .
                $this->getSvgTransformMatrix(
                    $character_matrix,
                    $margin + $x_offset,
                    $y_bottom - $margin - (Config::getConfig('svg/character-size'))[1],
                    $scale_factor,
                    $scale_factor
                ) .
                '" fill="' . $foreground->getCssRgbaText() . '" stroke="none">' . PHP_EOL;
            $svg .= "\t\t" . '<path d="' . $character_path . '" style="fill-rule:' . $character_fill_rule . ';" />' . PHP_EOL;
            $svg .= "\t</g>" . PHP_EOL;
        } catch (\Exception $e) {
            throw new Exception(ErrorLevel::Error, "E_SVG_INVALID",
                'Defective character is "' . $code_character . '" -- ' . $e->getMessage(), $e);
        }
        
        return $svg;
    }
    
    public function getEpsCharacterPath(string $code_character, float $x_offset, float $y_bottom, int $margin,
                                        float $scale_factor = 1, ?Color $foreground = null) : string
    {
        if (is_null($foreground)) {
            $foreground = new Color([0, 0, 0, 1.0]);
        }
        
        try {
            $character_matrix = Config::getConfig('svg/character/' . $code_character . '/matrix');
            $character_path = Config::getConfig('svg/character/' . $code_character . '/path');
            $character_fill_rule = strtolower(Config::getConfigOrSetIfUndefined('svg/character/' . $code_character . '/fill-rule', "nonzero"));
            
            if (preg_match('/[^CcMmLlZz0-9.\-\s,]/', $character_path)) {
                throw new Exception(ErrorLevel::Error, "E_EPS_CONVERSION_FAILURE",
                    'The character "' . $code_character . '" has unsupported operators.');
            }
            
            $eps = "gsave" . PHP_EOL;
            $eps .= $this->getEpsCoordinateMatrix(
                $character_matrix,
                $y_bottom,
                $margin + $x_offset,
                $y_bottom - $margin - (Config::getConfig('svg/character-size'))[1],
                $scale_factor,
                $scale_factor
            ) . PHP_EOL;
            
            /*
            $path_set = preg_match_all(
                '/(?|(M)([0-9.\-]+)[\s,]+([0-9.\-]+)|(L)([0-9.\-]+)[\s,]+([0-9.\-]+)|(C)([0-9.\-]+)[\s,]+([0-9.\-]+)[\s,]+([0-9.\-]+)[\s,]+([0-9.\-]+)[\s,]+([0-9.\-]+)[\s,]+([0-9.\-]+)|(Z))/',
                $character_path,
                $path_set,
                PREG_SET_ORDER,
            );
            */
            
            $eps .= "newpath" . PHP_EOL;
            // PHP_EOL should be placed before the substitution to avoid incorrect matches in subsequent processes.
            $eps .= preg_replace_callback_array(
                [
                    '/(C|c)([0-9.\-]+)[\s,]+([0-9.\-]+)[\s,]+([0-9.\-]+)[\s,]+([0-9.\-]+)[\s,]+([0-9.\-]+)[\s,]+([0-9.\-]+)/'
                        => fn($m) => PHP_EOL . $m[2] . " " . $m[3] . " " . $m[4] . " " . $m[5] . " " . $m[6] . " " . $m[7] . " " . ($m[1] == "C" ? "curveto" : "rcurveto"),
                    '/(M|m)([0-9.\-]+)[\s,]+([0-9.\-]+)/'
                        => fn($m) => PHP_EOL . $m[2] . " " . $m[3] . " " . ($m[1] == "M" ? "moveto" : "rmoveto"),
                    '/(L|l)([0-9.\-]+)[\s,]+([0-9.\-]+)/'
                        => fn($m) => PHP_EOL . $m[2] . " " . $m[3] . " " . ($m[1] == "L" ? "lineto" : "rlineto"),
                    '/(Z|z)/' => fn($m) => PHP_EOL . "closepath",
                ],
                $character_path
            ) . PHP_EOL;
            
            $eps .= $foreground->getEpsRgbText() ." setrgbcolor". PHP_EOL;
            $eps .= ($character_fill_rule == "evenodd" ? "eofill" : "fill") . PHP_EOL;
            $eps .= "grestore" . PHP_EOL;
            
        } catch (\Exception $e) {
            throw new Exception(ErrorLevel::Error, "E_EPS_CONVERSION_FAILURE",
                'Defective character is  "' . $code_character . '"' . $e->getMessage(), $e);
        }
        
        return $eps;
    }
    
    public function getScaledSvg(string $svg_body, int $width, int $height,
                                 int $scale_factor_w = 1, int $scale_factor_h = 1) : string
    {
        $svg = '<?xml version="1.0" standalone="no" ?>' . PHP_EOL;
        $svg .= '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' . PHP_EOL;
        $svg .= '<svg width="' . $width * $scale_factor_w . '" height="' . $height * $scale_factor_h .
            '" viewBox="0 0 ' . $width * $scale_factor_w . ' ' . $height * $scale_factor_h .
            '" version="1.1" xmlns="http://www.w3.org/2000/svg">' . PHP_EOL;
        $svg .= "\t" . '<desc>' . $this->code . '</desc>' . PHP_EOL;
        if ($scale_factor_w == 1 && $scale_factor_h == 1) {
            $svg .= $svg_body;
        } else {
            $svg .= "\t" . '<g transform="matrix(' . $scale_factor_w . ',0,0,' . $scale_factor_h . ',0,0)">' . PHP_EOL;
            $svg .= $svg_body;
            $svg .= "\t" . '</g>' . PHP_EOL;
        }
        $svg .= '</svg>' . PHP_EOL;
        
        return $svg;
    }
    
    public static function Instantiate(BarcodeParameter $barcode_parameter) : BarcodeJan13
    {
        return new BarcodeJan13($barcode_parameter->code, $barcode_parameter->height, $barcode_parameter->numbered,
            $barcode_parameter->margin, $barcode_parameter->width_factor, $barcode_parameter->foreground,
            $barcode_parameter->background);
    }
}