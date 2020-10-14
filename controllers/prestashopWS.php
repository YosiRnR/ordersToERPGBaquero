<?php

require_once __DIR__.'/PSWebServiceLibrary.php';


class PrestaShopWS
{
    private static $SHOP_URL = "";
    private static $API_KEY  = "";
    
    private static $web_service = null;


    private function __construct()
    {

    }
    private function __clone()
    {

    }
    
    public static function Connect(): PrestaShopWebService
    {
        if (!self::$web_service)
        {
            self::$web_service = new PrestaShopWebService(self::$SHOP_URL, self::$API_KEY, false);
        }

        return self::$web_service;
    }

    public static function GET_SHOP_URL()
    {
        return self::$SHOP_URL;
    }

    public static function GET_API_KEY()
    {
        return self::$API_KEY;
    }

    public static function setAPIKey($apiKey)
    {
        self::$API_KEY = $apiKey;
    }

    public static function setShopURL($shopURL)
    {
        self::$SHOP_URL = $shopURL;
    }
}

?>
