<?php

namespace StudioDemmys\barcode\type;

use StudioDemmys\barcode\Common;
use StudioDemmys\barcode\Config;
use StudioDemmys\barcode\Exception;
use StudioDemmys\barcode\Logging;
use StudioDemmys\barcode\type;

class BarcodeParameter
{
    public ?string $code = null;
    public ?int $width_factor = null;
    public ?int $height = null;
    public ?bool $numbered = null;
    public ?int $margin = null;
    public ?Color $background = null;
    public ?Color $foreground = null;
    public ?ImageFormat $format = null;
    public ?bool $download = null;
    
    public function __construct(array $user_input)
    {
        try {
            $this->setCode($user_input);
            $this->setWidthFactor($user_input);
            $this->setHeight($user_input);
            $this->setNumbered($user_input);
            $this->setMargin($user_input);
            $this->setBackgroundColor($user_input);
            $this->setForegroundColor($user_input);
            $this->setFormat($user_input);
            $this->setDownload($user_input);
        } catch (Exception $e) {
            throw new Exception(ErrorLevel::Error, "E_PARAMETER_FAILURE", $e->getMessage(), $e);
        }
    }
    
    protected function setCode(array $user_input) : void
    {
        $code = Common::sanitizeUserInput($user_input["code"] ??
            Config::getConfigOrSetIfUndefined("default/width_factor", "012345678901"));
        Logging::debug("Input: code={$code}");
        if (!preg_match('/^\d{1,13}$/', $code)) {
            throw new Exception(ErrorLevel::Error, "E_PARAMETER_CODE_INVALID", 'Defective code is "' . $code . '"');
        }
        Logging::debug("Actual code is {$code}");
        $this->code = $code;
    }
    
    protected function setWidthFactor(array $user_input) : void
    {
        $width_factor = intval(Common::sanitizeUserInput($user_input["width_factor"] ??
            Config::getConfigOrSetIfUndefined("default/width_factor", 2)));
        Logging::debug("Input: width_factor={$width_factor}");
        
        $min = intval(Config::getConfigOrSetIfUndefined("limit/width_factor/min", 1));
        if ($min < 1) {
            $min = 1;
        }
        $max = intval(Config::getConfigOrSetIfUndefined("limit/width_factor/max", 10));
        if ($max >= 0 && $max < $min) {
            Logging::debug("The max value of width factor ({$max}) is overridden by the min value ({$min}).");
            $max = $min;
        }
        
        if ($width_factor < $min) {
            Logging::debug("Width factor is too small");
            $width_factor = $min;
        }
        if ($max >= 0 && $width_factor > $max) {
            Logging::debug("Width factor is too large");
            $width_factor = $max;
        }
        Logging::debug("Actual width factor is {$width_factor}");
        $this->width_factor = $width_factor;
    }
    
    protected function setHeight(array $user_input) : void
    {
        if (is_null($this->width_factor)) {
            throw new Exception(ErrorLevel::Error, "E_PARAMETER_INTERNAL_PROBLEM","Width factor is not set yet!");
        }
        
        $height = intval(Common::sanitizeUserInput($user_input["height"] ??
            Config::getConfigOrSetIfUndefined("default/height", 40)));
        Logging::debug("Input: height={$height}");
        
        $min = max(
            Config::getConfigOrSetIfUndefined("limit/height/min", 1),
            // The height of characters is (svg/character-size)[1], that of blank is 2 px, and that of bars is 1 px
            ((Config::getConfig('svg/character-size'))[1] + 2 + 1) * $this->width_factor
        );
        $max = Config::getConfigOrSetIfUndefined("limit/height/max", 1000);
        if ($max >= 0 && $max < $min) {
            Logging::debug("The max value of height ({$max}) is overridden by the min value ({$min}).");
            $max = $min;
        }
        
        if ($height < $min) {
            Logging::debug("Height is too small");
            $height = $min;
        }
        if ($max >= 0 && $height > $max) {
            Logging::debug("Height is too large");
            $height = $max;
        }
        Logging::debug("Actual height is {$height}");
        $this->height = $height;
    }
    
    protected function setNumbered(array $user_input) : void
    {
        $numbered = Common::sanitizeUserInput($user_input["numbered"] ??
            Config::getConfigOrSetIfUndefined("default/numbered", "0"));
        Logging::debug("Input: numbered={$numbered}");
        
        $numbered_flag = ($numbered == "1");
        Logging::debug("Actual numbered flag is " . ($numbered_flag ? "true" : "false"));
        $this->numbered = $numbered_flag;
    }
    
    protected function setMargin(array $user_input) : void
    {
        if (is_null($this->height)) {
            throw new Exception(ErrorLevel::Error, "E_PARAMETER_INTERNAL_PROBLEM","Height is not set yet!");
        }
        if (is_null($this->width_factor)) {
            throw new Exception(ErrorLevel::Error, "E_PARAMETER_INTERNAL_PROBLEM","Width factor is not set yet!");
        }
        
        $margin = intval(Common::sanitizeUserInput($user_input["margin"] ??
            Config::getConfigOrSetIfUndefined("default/margin", 2)));
        Logging::debug("Input: margin={$margin}");
        
        $min = Config::getConfigOrSetIfUndefined("limit/margin/min", 0);
        if ($min < 0) {
            $min = 0;
        }
        $max = Config::getConfigOrSetIfUndefined("limit/margin/max", -1);
        if ($max >= 0 && $max < $min) {
            Logging::debug("The max value of margin ({$max}) is overridden by the min value ({$min}).");
            $max = $min;
        }
        
        if ($margin < $min) {
            Logging::debug("Margin is too small");
            $margin = $min;
        }
        
        // The height of characters is (svg/character-size)[1], that of blank is 2 px, and that of bars is 1 px,
        // so the available margin is half of (the image height - ((svg/character-size)[1] + 2 + 1))
        $available_margin = floor(($this->height - (((Config::getConfig('svg/character-size'))[1] + 2 + 1) * $this->width_factor)) / 2);
        Logging::debug("The available margin is {$available_margin}");
        if ($margin > $available_margin) {
            Logging::debug("Margin is reduced to {$available_margin}");
            $margin = $available_margin;
        }
        if ($max >= 0 && $margin > $max) {
            Logging::debug("Margin is too large");
            $margin = $max;
        }
        Logging::debug("Actual margin is {$margin}");
        $this->margin = $margin;
    }
    
    protected function setBackgroundColor(array $user_input) : void
    {
        if (empty($user_input["background"]) || !is_string($user_input["background"])) {
            $background = new type\Color(Config::getConfigOrSetIfUndefined("default/color/background", [255, 255, 255, 1]));
            Logging::debug("Input: background=" . $background->getCssRgbaText());
        } else {
            $background_input = array_map('trim', explode(',', Common::sanitizeUserInput($user_input["background"])));
            Logging::debug("Input: background=" . implode(',', $background_input));
            $background = new type\Color($background_input);
        }
        
        Logging::debug("Actual background is " . $background->getCssRgbaText());
        $this->background = $background;
    }
    
    protected function setForegroundColor(array $user_input) : void
    {
        if (empty($user_input["foreground"]) || !is_string($user_input["foreground"])) {
            $foreground = new type\Color(Config::getConfigOrSetIfUndefined("default/color/foreground", [0, 0, 0, 1]));
            Logging::debug("Input: foreground=" . $foreground->getCssRgbaText());
        } else {
            $foreground_input = array_map('trim', explode(',', Common::sanitizeUserInput($user_input["foreground"])));
            Logging::debug("Input: foreground=" . implode(',', $foreground_input));
            $foreground = new type\Color($foreground_input);
        }
        
        Logging::debug("Actual foreground is " . $foreground->getCssRgbaText());
        $this->foreground = $foreground;
    }
    
    protected function setFormat(array $user_input) : void
    {
        $format_string = strtolower(Common::sanitizeUserInput($user_input["format"] ??
            Config::getConfigOrSetIfUndefined("default/format", "png")));
        Logging::debug("Input: format={$format_string}");
        
        $format = ImageFormat::tryFrom($format_string);
        if (is_null($format)) {
            Logging::debug("Invalid format, falling back to png");
            $format = ImageFormat::Png;
        }
        
        Logging::debug("Actual image format is " . $format->value);
        $this->format = $format;
    }
    
    protected function setDownload(array $user_input) : void
    {
        if (is_null($this->format)) {
            throw new Exception(ErrorLevel::Error, "E_PARAMETER_INTERNAL_PROBLEM","Format is not set yet!");
        }
        
        $download = Common::sanitizeUserInput(
            $user_input["download"] ??
            match ($this->format) {
                ImageFormat::Png => Config::getConfigOrSetIfUndefined("default/attachment/png", "0"),
                ImageFormat::Svg => Config::getConfigOrSetIfUndefined("default/attachment/svg", "0"),
                ImageFormat::Eps => Config::getConfigOrSetIfUndefined("default/attachment/Eps", "1"),
                default => throw new Exception(ErrorLevel::Error, "E_IMAGE_FORMAT_UNSUPPORTED",
                    'The requested format is "' . $this->format->value . '"'),
            }
        );
        Logging::debug("Input: download={$download}");
        
        $download_flag = ($download == "1");
        Logging::debug("Actual download flag is " . ($download_flag ? "true" : "false"));
        $this->download = $download_flag;
    }
}