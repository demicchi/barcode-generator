<?php

namespace StudioDemmys\barcode\type;

class Color
{
    public int $r;
    public int $g;
    public int $b;
    public float $a;
    
    public function __construct(array $color = [0, 0, 0, 1])
    {
        $this->r = intval($color[0] ?? 0);
        $this->r = max(0, $this->r);
        $this->r = min(255, $this->r);
        
        $this->g = intval($color[1] ?? 0);
        $this->g = max(0, $this->g);
        $this->g = min(255, $this->g);
        
        $this->b = intval($color[2] ?? 0);
        $this->b = max(0, $this->b);
        $this->b = min(255, $this->b);
        
        $this->a = floatval($color[3] ?? 1);
        $this->a = max(0, $this->a);
        $this->a = min(1, $this->a);
    }
    
    public function getCssRgbaText() : string
    {
        return 'rgba(' . $this->r . ',' . $this->g . ',' . $this->b . ',' . sprintf('%01.2f', $this->a) .')';
    }
    
    public function getCssRgbText() : string
    {
        return 'rgb(' . $this->r . ',' . $this->g . ',' . $this->b .')';
    }
    
    public function getEpsRgbText() : string
    {
        return sprintf('%01.2f %01.2f %01.2f', $this->r / 255, $this->g / 255, $this->b / 255);
    }
}