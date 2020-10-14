<?php
require_once __DIR__.'/prestashopWS.php';
require_once __DIR__.'/ERPClient.php';
require_once __DIR__.'/productosController.php';
require_once __DIR__.'/clientesController.php';

$pc = new PedidosController();
$pc->enviarPedidoDeTiendaAlERP(53, "https://tienda.garciabaquero.com/", "ZJHB655M3Y6UKDUBZGRREIAJK33EWBFS");

class PedidosController
{
    public function enviarPedidoDeTiendaAlERP($id, $shopURL, $apiKey)
    {
        PrestaShopWS::setShopURL($shopURL);
        PrestaShopWS::setAPIKey($apiKey);

        $authorizationKey = base64_encode($apiKey . ':');

        try
        {
            $opt = [];
            $opt['resource'] = 'orders';
            $opt['id'] = $id;
            // $opt['url'] = PrestaShopWS::GET_SHOP_URL().'api/orders/'.$id.'?ws_key='.PrestaShopWS::GET_API_KEY();
            $xml = PrestaShopWS::Connect()->get($opt);
            $pedido = $xml->children()->children();

            $direccion_envio = $this->obtenerDireccionDeEnvio($pedido->id_address_delivery);
            $direccion_factu = $this->obtenerDireccionDeEnvio($pedido->id_address_invoice);

            // $url = PrestaShopWS::GET_SHOP_URL().'api/orders/'.$id.'?ws_key='.PrestaShopWS::GET_API_KEY().'&output_format=JSON';
            // file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').' - '.'Procesando pedido '.$id.PHP_EOL, FILE_APPEND | LOCK_EX);
            // file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').' - '.$url.PHP_EOL, FILE_APPEND | LOCK_EX);
            // $arrContextOptions=array(
            //     "ssl"=>array(
            //           "verify_peer"=>false,
            //           "verify_peer_name"=>false,
            //       ),
            //   );
            // $pedido = file_get_contents($url, false, stream_context_create($arrContextOptions));
            // file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').' - '.$pedido.PHP_EOL, FILE_APPEND | LOCK_EX);

            // $pedido = $xml->children()->children();

            // $pedidoArray = json_decode($pedido);

            // $direccion_envio = $this->obtenerDireccionDeEnvio($pedidoArray->order->id_address_delivery);
            // file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').' - Direccion envio: '.$direccion_envio.PHP_EOL, FILE_APPEND | LOCK_EX);
            // $direccion_factura = $this->obtenerDireccionDeEnvio($pedidoArray->order->id_address_invoice);
            // file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').' - Direccion facturacion: '.$direccion_factura.PHP_EOL, FILE_APPEND | LOCK_EX);
            // $provincia_envio = obtenerProvincia($direccion_envio->id_state);
            // $pais_envio      = obtenerPais($direccion_envio->id_country);
            // $mensaje_pedido  = $this->obtenerMensajeDeEnvio($pedido->id);
                
            // $client_ctrl = new ClientesController();
            // $cliente     = $client_ctrl->obtenerClienteDeTienda($direccion_envio->id_customer);

            // $result = array_merge(json_decode($pedido, true), json_decode($direccion_envio, true));
            // $result['address_delivery'] = $result['address'];
            // unset($result['address']);
            
            // $result = array_merge($result, json_decode($direccion_factura, true));
            // $result['address_invoice'] = $result['address'];
            // unset($result['address']);
            
            if (!is_dir('pedidosSAP/')) mkdir('pedidosSAP/');

            // file_put_contents("pedidosSAP/deltanet_pedidos_".$id.".json", json_encode($result, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), FILE_APPEND|LOCK_EX);
    
            file_put_contents('deltanet_pedido_'.$pedido->id.'.xml', $xml->asXML(), FILE_APPEND | LOCK_EX);
            file_put_contents('deltanet_pedido_'.$pedido->id.'_envio.xml', $direccion_envio->asXML(), FILE_APPEND | LOCK_EX);
            file_put_contents('deltanet_pedido_'.$pedido->id.'_factu.xml', $direccion_factu->asXML(), FILE_APPEND | LOCK_EX);
        }
        catch(Exception $ex)
        // catch(PrestaShopWebServiceException $ex)
        {
            file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').'- EXCEPTION (enviarPedidoDeTiendaAlERP): '.$ex->getMessage().' en linea '.$ex->getLine().PHP_EOL, FILE_APPEND | LOCK_EX);
            // file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').'- ID PEDIDO: '.$pedido->id.PHP_EOL.'API KEY: '.$apiKey.PHP_EOL, FILE_APPEND | LOCK_EX);
            // file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').'- URL: '.$url.PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    public function obtenerDireccionDeEnvio($id)
    {
        $direccion = null;
        file_put_contents('deltanet_errors.log', 'ID DIRECCION: '.$id.PHP_EOL, FILE_APPEND | LOCK_EX);
        try
        {
            $opt = [];
            $opt['resource'] = 'addresses';
            $opt['id'] = $id;
            // $opt['url'] = PrestaShopWS::GET_SHOP_URL().'api/addresses/'.$id.'?ws_key='.PrestaShopWS::GET_API_KEY();
            $xml = PrestaShopWS::Connect()->get($opt);
            $direccion = $xml;
            // $url = PrestaShopWS::GET_SHOP_URL().'api/addresses/'.$id.'?ws_key='.PrestaShopWS::GET_API_KEY().'&output_format=JSON';
            // file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').' - '.'Dirección '.$id.PHP_EOL, FILE_APPEND | LOCK_EX);
            // file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').' - '.$url.PHP_EOL, FILE_APPEND | LOCK_EX);
            // $arrContextOptions=array(
            //     "ssl"=>array(
            //           "verify_peer"=>false,
            //           "verify_peer_name"=>false,
            //       ),
            //   );
            // $direccion = file_get_contents($url, false, stream_context_create($arrContextOptions));
            // $dirArray = json_decode($direccion);
            // $countryID = $dirArray->address->id_country;
            // $url = PrestaShopWS::GET_SHOP_URL().'api/countries/'.$countryID.'?ws_key='.PrestaShopWS::GET_API_KEY().'&output_format=JSON';
            // $country = file_get_contents($url, false, stream_context_create($arrContextOptions));
            // $dirArray->address->country = json_decode($country, true);
            // $stateID = $dirArray->address->id_state;
            // $url = PrestaShopWS::GET_SHOP_URL().'api/states/'.$stateID.'?ws_key='.PrestaShopWS::GET_API_KEY().'&output_format=JSON';
            // $state = file_get_contents($url, false, stream_context_create($arrContextOptions));
            // $dirArray->address->state = json_decode($state, true);
            // $direccion = json_encode($dirArray);
            // file_put_contents('deltanet_errors.log', print_r($xml, true), FILE_APPEND | LOCK_EX);
            //$direccion = $xml;//->children()->children();
        }
        catch(Exception $ex)
        // catch(PrestaShopWebServiceException $ex)
        {
            file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').'- EXCEPTION (obtenerDireccionDeEnvio): '.$ex->getMessage().PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        finally
        {
            return $direccion;
        }
    }

    public function obtenerMensajeDeEnvio($id)
    {
        $mensaje_pedido = null;

        try
        {
            $opt = [];
            // $opt['resource'] = 'messages';
            // $opt['filter[id_order]'] = '['.$id.']';
            // $opt['display'] = 'full';
            // $opt['url'] = PrestaShopWS::GET_SHOP_URL().'api/messages?filter[id_order]=['.$id.']&display=full&ws_key='.PrestaShopWS::GET_API_KEY();
            // $xml = PrestaShopWS::Connect()->get($opt);
            $xml = new SimpleXMLElement(file_get_contents(PrestaShopWS::GET_SHOP_URL().'api/messages?filter[id_order]=['.$id.']&display=full&ws_key='.PrestaShopWS::GET_API_KEY()));
            $mensaje_pedido = $xml->children()->children();
        }
        catch(Exception $ex)
        // catch(PrestaShopWebServiceException $ex)
        {
            file_put_contents('deltanet_errors.log', date('d/m/Y H:m:s').'- EXCEPTION (obtenerMensajeDeEnvio): '.$ex->getMessage().PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        finally
        {
            return $mensaje_pedido;
        }
    }
}

?>
