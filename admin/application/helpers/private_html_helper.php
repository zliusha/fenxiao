<?php
function include_head($title='',$html='')
{

 echo <<<HTML
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统->{$title}</title>
HTML;
echo static_plus_url('bootstrap','bootstrap.min.css');
echo static_plus_url('hplus','font-awesome.min.css');
echo static_plus_url('hplus','animate.min.css');
echo static_plus_url('bootstrap-table','bootstrap-table.min.css');
echo static_site_url('admin','style.css');
echo $html;
echo "</head>";
}