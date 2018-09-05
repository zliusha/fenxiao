<?php

/**
 * @Author: binghe
 * @Date:   2018-07-24 16:57:03
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-10 17:02:08
 */
class Saas extends dist_controller
{
	public function index()
	{
		$this->home();
	}
    //saas中心
    public function home()
    {
        $this->dist('dj_home/index.html');
    }
    //ydym
    public function ydym()
    {
        $this->dist('dj_ydym/index.html');
    }
    //hcity
    public function hcity()
    {
        $this->dist('dj_hcity/index.html');
    }
    //hcity h5
    public function mhcity()
    {
        $this->dist('dj_mhcity/index.html');
    }
    //城市合伙人后台
    public function hcity_admin()
    {
        $this->dist('dj_hcity_admin/index.html');
    }
}
