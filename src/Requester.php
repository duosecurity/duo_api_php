<?php
namespace DuoAPI;

interface Requester
{
    public function options($options);
    public function execute($url, $methods, $headers, $body);
}
