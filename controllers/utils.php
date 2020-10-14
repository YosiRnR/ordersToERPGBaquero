<?php

function formatHTMLString($cadenaTexto): string
{
    $cadenaTexto = str_replace("\n", "<br />", $cadenaTexto);
    $cadenaTexto = str_replace("&lt", "<", $cadenaTexto);
    $result = str_replace("&rt", ">", $cadenaTexto);

    return result;
}

function getImageMimeType($imagedata)
{
    $imagemimetypes = array( 
        "jpg"  => "FFD8", 
        "png"  => "89504E470D0A1A0A", 
        "gif"  => "474946",
        "bmp"  => "424D", 
        "tiff" => "4949",
        "tiff" => "4D4D"
    );
    
    foreach ($imagemimetypes as $mime => $hexbytes)
    {
        $bytes = $this->getBytesFromHexString($hexbytes);

        if (substr($imagedata, 0, strlen($bytes)) == $bytes)
        {
            return $mime;
        }
    }
    
    return NULL;
}

function obtenerProvincia($id)
{
    try
    {
        $opt = [];
        // $opt['resource'] = 'states';
        // $opt['id'] = $id;
        // $opt['url'] = PrestaShopWS::GET_SHOP_URL().'api/states/'.$id.'?ws_key='.PrestaShopWS::GET_API_KEY();
        // $xml = PrestaShopWS::Connect()->get($opt);
        $xml = new SimpleXMLElement(file_get_contents(PrestaShopWS::GET_SHOP_URL().'api/states/'.$id.'?ws_key='.PrestaShopWS::GET_API_KEY()));
        $state = $xml->children()->children();

        return $state;
    }
    catch(Exception $ex)
    // catch(PrestaShopWebServiceException $ex)
    {
        file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').'- EXCEPTION (obtenerProvincia): '.$ex->getMessage().PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

function obtenerPais($id)
{
    try
    {
        $opt = [];
        // $opt['resource'] = 'countries';
        // $opt['id'] = $id;
        // $opt['url'] = PrestaShopWS::GET_SHOP_URL().'api/countries/'.$id.'?ws_key='.PrestaShopWS::GET_API_KEY();
        // $xml = PrestaShopWS::Connect()->get($opt);
        $xml = new SimpleXMLElement(file_get_contents(PrestaShopWS::GET_SHOP_URL().'api/countries/'.$id.'?ws_key='.PrestaShopWS::GET_API_KEY()));
        $pais = $xml->children()->children();

        return $pais;
    }
    // catch(PrestaShopWebServiceException $ex)
    catch(Exception $ex)
    {
        file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').'- EXCEPTION (obtenerPais): '.$ex->getMessage().PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

?>
