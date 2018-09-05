<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/27
 * Time: 上午11:33
 */

namespace Service\Support;

use Service\Exceptions\ShutDownException;
use Service\Traits\SingletonTrait;

class Shutdown
{
    use SingletonTrait;

    /**
     * 改写register_shutdown_function
     *
     * @param $function
     * @param null $parameter
     * @param null $options
     * @author ahe<ahe@iyenei.com>
     */
    public function register($function, $parameter = null, $options = null)
    {
        if (IS_CLI) {
            $GLOBALS['_shutdown_'][] = ['function' => $function, 'parameter' => $parameter];
        } else {
            register_shutdown_function($function, $parameter, $options);
        }
    }

    /**
     * cli模式下触发自定义shutdown方法
     *
     * @return bool
     * @throws ShutDownException
     * @author ahe<ahe@iyenei.com>
     */
    public function trigger()
    {
        if (!IS_CLI) {
            return false;
        }
        try {
            if (!empty($GLOBALS['_shutdown_'])) {
                foreach ($GLOBALS['_shutdown_'] as $val) {
                    if (is_callable($val['function'])) {
                        call_user_func($val['function'], $val['parameter']);
                    } elseif (is_string($val['function'])) {
                        $val['function']($val['parameter']);
                    }
                }
                return true;
            }
        } catch (\Throwable $e) {
            throw new ShutDownException($e->getMessage());
        } finally {
            $GLOBALS['_shutdown_'] = [];
        }
        return false;
    }
}