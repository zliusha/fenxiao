<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends base_controller {
	public function index()
	{
		$this->load->view('index');
	}
}
