<?php
/**
 * @Author: binghe
 * @Date:   2018-04-09 10:42:28
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-09 11:05:25
 */
namespace Service\Traits;
use Service\Support\Http;
trait HttpTrait
{
    /**
     * Http instance.
     *
     * @var Http
     */
    protected $http;

    /**
     * Return the http instance.
     *
     * @return \Service\Support\Http
     */
    public function getHttp()
    {
        return $this->http ?: $this->http = new Http();
    }

    /**
     * Set the http instance.
     *
     * @param \Service\Support\Http $http
     *
     * @return $this
     */
    public function setHttp(Http $http)
    {
        $this->http = $http;

        return $this;
    }

}