<?php
/**
 * 二维码生成
 */
class ci_qrcode
{
    public $cacheable = true;
    public $cachedir = 'cache/';
    public $errorlog = 'logs/';
    public $quality = true;
    public $size = 1024;

    public function __construct($config = array())
    {
        $this->initialize($config);
    }

    public function initialize($config = array())
    {
        $this->cacheable = (isset($config['cacheable'])) ? $config['cacheable'] : $this->cacheable;
        $this->cachedir = (isset($config['cachedir'])) ? $config['cachedir'] : APPPATH . $this->cachedir;
        $this->errorlog = (isset($config['errorlog'])) ? $config['errorlog'] : APPPATH . $this->errorlog;
        $this->quality = (isset($config['quality'])) ? $config['quality'] : $this->quality;
        $this->size = (isset($config['size'])) ? $config['size'] : $this->size;

        // use cache - more disk reads but less CPU power, masks and format templates are stored there
        if (!defined('QR_CACHEABLE')) {
            define('QR_CACHEABLE', $this->cacheable);
        }

        // used when QR_CACHEABLE === true
        if (!defined('QR_CACHE_DIR')) {
            define('QR_CACHE_DIR', $this->cachedir);
        }

        // default error logs dir
        if (!defined('QR_LOG_DIR')) {
            define('QR_LOG_DIR', $this->errorlog);
        }

        // if true, estimates best mask (spec. default, but extremally slow; set to false to significant performance boost but (propably) worst quality code
        if ($this->quality) {
            if (!defined('QR_FIND_BEST_MASK')) {
                define('QR_FIND_BEST_MASK', true);
            }

        } else {
            if (!defined('QR_FIND_BEST_MASK')) {
                define('QR_FIND_BEST_MASK', false);
            }

            if (!defined('QR_DEFAULT_MASK')) {
                define('QR_DEFAULT_MASK', $this->quality);
            }

        }

        // if false, checks all masks available, otherwise value tells count of masks need to be checked, mask id are got randomly
        if (!defined('QR_FIND_FROM_RANDOM')) {
            define('QR_FIND_FROM_RANDOM', false);
        }

        // maximum allowed png image width (in pixels), tune to make sure GD and PHP can handle such big images
        if (!defined('QR_PNG_MAXIMUM_SIZE')) {
            define('QR_PNG_MAXIMUM_SIZE', $this->size);
        }

        // call original library
        include "qrcode/qrconst.php";
        include "qrcode/qrtools.php";
        include "qrcode/qrspec.php";
        include "qrcode/qrimage.php";
        include "qrcode/qrinput.php";
        include "qrcode/qrbitstream.php";
        include "qrcode/qrsplit.php";
        include "qrcode/qrrscode.php";
        include "qrcode/qrmask.php";
        include "qrcode/qrencode.php";
    }

    /**
     * QR生成器
     * @param  array  $params 参数
     * bg   array(255,255,255)  背景色 默认白色 RGB形式
     * fg   array(0,0,0) 前景色 默认黑色
     * data string 内容
     * savename     string  保存路径    （存在的时候保存到路径，不存在的时候需要）,返回路径
     * show bool 当存在savename时候是否直接输出
     * level  'L','M','Q','H'  纠错等级 ；纠错等级越高 可存储得数据越少，识读效率越高
     * size int 大小 默认为4 (132x132) 尺寸 33xsize
     */
    public function generate($params = array())
    {
        if (isset($params['bg'])
            && is_array($params['bg'])
            && count($params['bg']) == 3
            && array_filter($params['bg'], 'is_int') === $params['bg']) {
            QRimage::$black = $params['bg'];
        }

        if (isset($params['fg'])
            && is_array($params['fg'])
            && count($params['fg']) == 3
            && array_filter($params['fg'], 'is_int') === $params['fg']) {
            QRimage::$white = $params['fg'];
        }

        $params['data'] = (isset($params['data'])) ? $params['data'] : 'QR Code Library';
        if (isset($params['savename'])) {
            $level = 'L';
            if (isset($params['level']) && in_array($params['level'], array('L', 'M', 'Q', 'H'))) {
                $level = $params['level'];
            }

            $size = 4;
            if (isset($params['size'])) {
                $size = min(max((int) $params['size'], 1), 10);
            }

            if (isset($params['show']) && $params['show']) {
                QRcode::png($params['data'], $params['savename'], $level, $size, 2, true);
            } else {
                QRcode::png($params['data'], $params['savename'], $level, $size, 2);
            }
            return $params['savename'];
        } else {
            $level = 'L';
            if (isset($params['level']) && in_array($params['level'], array('L', 'M', 'Q', 'H'))) {
                $level = $params['level'];
            }

            $size = 4;
            if (isset($params['size'])) {
                $size = min(max((int) $params['size'], 1), 10);
            }

            QRcode::png($params['data'], null, $level, $size, 2);
        }
    }

}
