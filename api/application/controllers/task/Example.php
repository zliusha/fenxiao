<?php
/**
 * @Author: binghe
 * @Date:   2018-04-28 09:44:06
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-28 16:20:51
 */
/**
* 试例
*/
class Example extends base_controller
{
    
    public function index()
    {
        for($count=1;$count<=100;$count++)
        {
            echo 'log - '.$count.'. now time is '.time().PHP_EOL;
            sleep(5);
        }
    }
}