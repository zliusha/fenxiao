<?php
/**
 * @Author: binghe
 * @Date:   2018-04-04 11:32:10
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-21 16:25:40
 */
/**
* acl
*/
class Acl
{
    private $exceptionHandler;
    public function setExceptionHandler()
    {
        $this->exceptionHandler = set_exception_handler(function($e){
            if ($e instanceof Exception) {
                $ci = CI_Controller::get_instance();

                // 如果有异常处理方法存在, 调用异常处理方法
                // 如果异常方法没有明确返回false, 退出异常处理
                if (method_exists($ci, 'onException')) {
                    $result = call_user_func([$ci, 'onException'], $e);
                    if ($result !== false) {
                        return ;
                    }
                }
            }
            if ($this->exceptionHandler != null) {
                call_user_func($this->exceptionHandler, $e);
            } else {
                throw $e;
            }
        });
    }
}