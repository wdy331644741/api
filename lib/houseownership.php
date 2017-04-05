<?php
namespace Lib;
class HouseOwnership {
    const CITY_TEXT_POS = ['x' => 190, 'y' => 70];
    const NAME_TEXT_POS = ['x' => 280, 'y' => 120];
    const ADDRESS_TEXT_POS = ['x' => 280, 'y' => 230];
    const RECORDTIME_TEXT_POS = ['x' => 280, 'y' => 345];
    const AREA_TEXT_POS = ['x' => 280, 'y' => 405];
    const TRUEAREA_TEXT_POS = ['x' => 535, 'y' => 405];

    private $backgroundImage = null;
    private $fontStyle = null;
    private $im = null;

    public function __construct()
    {   $this->backgroundImage = config_path('tpl/houseownership/images/houseownership.jpg');
        $this->fontStyle = config_path('tpl/houseownership/font/simhei.ttf');
        $this->im = imagecreatefromjpeg($this->backgroundImage);
    }

    public function __destruct()
    {
        if(!is_null($this->im)){
            imagedestroy($this->im);
        }
    }

    public function writeText($text,$position,$fontsize = 12)
    {
        $textcolor = imagecolorallocate($this->im, 55, 55, 55);
        imagettftext($this->im, $fontsize, 0, $position['x'], $position['y'], $textcolor, $this->fontStyle, $text);
        return $this;
    }

    public function showPng(){
        header('Content-type: image/png');
        imagepng($this->im);
    }

    public function getBase64Png(){
        ob_start();
        imagepng($this->im);
        $im = ob_get_clean();
        return base64_encode($im);
    }

}