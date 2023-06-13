<?php

namespace SiteCore\Helpers;

use DateTime;

class DbHelper
{
    public static function convertPhpTimeToMysqlDatetime($phpTime): string
    {
        $datetime = new DateTime();
        $datetime->setTimestamp($phpTime);
        return $datetime->format('Y-m-d H:i:s');
    }
}