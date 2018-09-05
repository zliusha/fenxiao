<?php

/**
 * 条形码生成
 * Class ci_barcode
 */
require_once ROOT.'vendor/autoload.php';

class ci_barcode
{
    /**
     * 获取不同图片类型的条形码实例
     * @param string $barcode [PNG,JPG,SVG,HTML]
     * @return mixed
     */
    public function getBarcodeInstance($barcode='PNG')
    {
        $class = "\Picqer\Barcode\BarcodeGenerator".$barcode;
        return new $class();
    }

}