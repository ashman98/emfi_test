<?php
namespace services;

trait AmoCrmAuthTrite
{
    protected $subdomain;
    protected $accessToken;

    /**
     * @param mixed $subdomain
     * @return void
     */
    public function setSubdomain($subdomain)
    {
        $this->subdomain = $subdomain;
    }

    /**
     * @param mixed $accessToken
     * @return void
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }
}