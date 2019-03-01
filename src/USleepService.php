<?php
namespace DuoAPI;

class USleepService implements SleepService
{
    public function sleep($seconds)
    {
        usleep($seconds * 1000000);
    }
}