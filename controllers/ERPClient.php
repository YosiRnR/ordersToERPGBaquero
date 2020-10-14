<?php

class ERPClient
{
    private const SOAP_URL = "http://serappmyegcr01.dyndns.org:8302/ERPAvanzaServiciosVO/services/ServiciosErpCliente?wsdl";

    private static $client = null;


    private function __construct()
    {

    }

    private function __clone()
    {

    }

    public static function Connect(): SoapClient
    {
        if (self::$client == null)
        {
            self::$client = new SoapClient(self::SOAP_URL,
                    ["encoding" => "UTF-8", "verifypeer" => false, "verifyhost" => false, "soap_version" => SOAP_1_2]);
        }

        return self::$client;
    }
}

?>
