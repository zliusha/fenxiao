<?php
/**
 * @Author: binghe
 * @Date:   2017-12-28 14:00:23
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-12-28 15:01:44
 */
function sysErrorLog($msg)
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

    /**
      function  string  当前函数名称
      line  integer 当前行号
      file  string  当前文件名
      class string  当前类名
      object object 当前对象
      type  string 当前调用类型。可能的调用： 返回: "->" - 方法调用 返回: "::" - 静态方法调用 返回 nothing - 函数调用
      args  array 如果在函数中，列出函数参数。如果在被引用的文件中，列出被引用的文件名。
     */
    $tagStr = '';
    if (isset($trace[1])) {
        $last = $trace[1];
        if (isset($last['class'])) {
            $tagStr = $last['class'];
        }
        if (!empty($last['type'])) {
            $tagStr .= $last['type'] == 'nothing' ? ' ' : $last['type'];
        }
        if (isset($last['function'])) {
            $tagStr .= $last['function'];
        }
        if (isset($trace[0]['line'])) {
            $tagStr .= ' (' . $trace[0]['line'] . ') ';
        }
    }
    log_message('error',$tagStr.'-'.$msg);

}