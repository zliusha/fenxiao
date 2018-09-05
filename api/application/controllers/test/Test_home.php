<?php
/**
 * @Author: binghe
 * @Date:   2018-05-25 17:48:38
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-05-25 18:00:02
 */
/**
* test home
*/
class Test_home extends base_controller
{
    
    public function index()
    {
        $data['title'] = 'service title';
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}