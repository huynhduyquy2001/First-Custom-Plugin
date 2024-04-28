<?php

namespace Acowebs\WCPA\Free;

class Cron
{
    static $key = 'wcpa_daily_event';

    public function __construct()
    {

    }

    static function schedule_cron()
    {
        if ( ! wp_next_scheduled(self::$key)) {
            wp_schedule_event(time(), 'daily', self::$key);

        }
    }



    static function clear()
    {
        wp_clear_scheduled_hook(self::$key);
        refreshCaches();
    }




}