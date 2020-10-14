<?php
require_once __DIR__.'/prestashopWS.php';
require_once __DIR__.'/ERPClient.php';

define('PRESTASHOP_GENDERS', [1 => 'Masculino', 2 => 'Femenino']);

class ClientesController
{
    public function crearArrayAsociativoDeTiposDeGruposDeClientes(): array
    {
        $prestashop_groups = array();

        try
        {
            $opt = [];
            $opt['resource'] = 'groups';
            $xml = PrestaShopWS::Connect()->get($opt);
            $groups_ids = $xml->children()->children();

            foreach($groups_ids as $item)
            {
                $opt['id'] = $item->attributes()->id;
                $xml = PrestaShopWS::Connect()->get($opt);
                $group = $xml->children()->children();

                $prestashop_groups[(string)$group->id] = $group->name->language[0];
            }

        }
        catch(PrestaShopWebServiceException $ex)
        {
            echo '<br />'.$ex->getMessage().'<br />';
        }
        finally
        {
            return $prestashop_groups;
        }
    }

    public function obtenerClientesDeTienda()
    {
        $PRESTASHOP_GROUPS = $this->crearArrayAsociativoDeTiposDeGruposDeClientes();

        try
        {
            $opt = [];
            $opt['resource'] = 'customers';
            $xml = PrestaShopWS::Connect()->get($opt);
            $clientes_ids = $xml->children()->children();

            $clientesERP = array();
            foreach($clientes_ids as $item)
            {
                $opt = [];
                $opt['resource'] = 'customers';
                $opt['id'] = $item->attributes()->id;
                $xml = PrestaShopWS::Connect()->get($opt);
                $cliente = $xml->children()->children();

                $clienteERP = array();
                $clienteERP['prestashop_id']       = $cliente->id;
                $clienteERP['apellidos']           = $cliente->lastname;
                $clienteERP['nombre']              = $cliente->firstname;
                $clienteERP['fecha_nacimiento']    = DateTime::createFromFormat("Y-m-d", $cliente->birthday)->format("d/m/Y");
                $clienteERP['genero']              = PRESTASHOP_GENDERS[(int)$cliente->id_gender];
                $clienteERP['email']               = $cliente->email;
                $clienteERP['fecha_creacion']      = DateTime::createFromFormat("Y-m-d H:i:s", $cliente->date_add)->format("d/m/Y H:i:s");
                $clienteERP['fecha_actualizacion'] = DateTime::createFromFormat("Y-m-d H:i:s", $cliente->date_upd)->format("d/m/Y H:i:s");
                $clienteERP['registrado']          = $cliente->is_guest == 0 ? 'true' : 'false';
                $clienteERP['activo']              = $cliente->active == 1 ? 'true' : 'false';
                $clienteERP['tipo_por_defecto']    = $PRESTASHOP_GROUPS[(string)$cliente->id_default_group];
                $clienteERP['newsletter']          = $cliente->newsletter == 1 ? 'true' : 'false';
                $clienteERP['recibir_ofertas']     = $cliente->optin == 1 ? 'true' : 'false';

                $opt = [];
                $opt['resource'] = 'addresses';
                $opt['filter[id_customer]'] = '['.$cliente->id.']';
                $xml = PrestaShopWS::Connect()->get($opt);
                $direcciones_cliente_ids = $xml->children()->children();

                $clienteERP['direcciones'] = array();

                foreach($direcciones_cliente_ids as $item)
                {
                    $opt = [];
                    $opt['resource'] = 'addresses';
                    $opt['id'] = $item->attributes()->id;
                    $xml = PrestaShopWS::Connect()->get($opt);
                    $direccion_cliente = $xml->children()->children();

                    $direccionClienteERP = array();
                    $direccionClienteERP['alias']         = $direccion_cliente->alias;
                    $direccionClienteERP['direccion1']    = $direccion_cliente->address1;
                    $direccionClienteERP['direccion2']    = $direccion_cliente->address2;
                    $direccionClienteERP['ciudad']        = $direccion_cliente->city;
                    $direccionClienteERP['codigo_postal'] = $direccion_cliente->postcode;
                    $direccionClienteERP['telefono']      = $direccion_cliente->phone;
                    $direccionClienteERP['dni']           = $direccion_cliente->dni;
                    $direccionClienteERP['apellidos']     = $direccion_cliente->lastname;
                    $direccionClienteERP['nombre']        = $direccion_cliente->firstname;

                    $clienteERP['direcciones'][] = $direccionClienteERP;
                }

                $clientesERP[] = $clienteERP;
            }

            echo '<pre>';
            print_r($clientesERP);

        }
        catch(PrestaShopWebServiceException $ex)
        {
            echo '<br />'.$ex->getMessage().'<br />';
        }
    }

    public function enviarClientesTiendaAlERP()
    {
        try
        {
            $opt = [];
            $opt['resource'] = 'customers';
            $opt['display'] = 'full';
            $xml = PrestaShopWS::Connect()->get($opt);
            $clientes = $xml->children()->children();

            foreach($clientes as $cliente)
            {
                if ($cliente->is_guest == 0)
                {
                    $clienteERP = array();
                    $clienteERP['idPrestaShop']   = $cliente->id;
                    $clienteERP['nombreCompleto'] = $cliente->firstname.' '.$cliente->lastname;
                    $clienteERP['email']          = $cliente->email;

                    $opt = [];
                    $opt['resource'] = 'addresses';
                    $opt['filter[id_customer]'] = '['.$cliente->id.']';
                    $opt['display'] = 'full';
                    $xml = PrestaShopWS::Connect()->get($opt);
                    $direcciones_cliente = $xml->children()->children();
                    
                    if (!empty($direcciones_cliente))
                    {
                        $direccion_cliente = $direcciones_cliente[0];

                        $pais      = obtenerPais($direccion_cliente->id_country);
                        $pais_name = $pais == null ? '' : $pais->name->language[0];
                        $provincia = obtenerProvincia($direccion_cliente->id_state);
                        $prov_name = $provincia == null ? '' : $provincia->name;

                        $clienteERP['nif']          = $direccion_cliente->dni;
                        $clienteERP['domicilio']    = $direccion_cliente->address1;
                        $clienteERP['codigoPostal'] = $direccion_cliente->postcode;
                        $clienteERP['poblacion']    = $direccion_cliente->city;
                        $clienteERP['provincia']    = $prov_name;
                        $clienteERP['pais']         = $pais_name;
                        $clienteERP['telefono1']    = $direccion_cliente->phone;
                        $clienteERP['telefono2']    = "";
                        $clienteERP['telefono3']    = "";
                    }
                    else
                    {
                        $clienteERP['nif']          = "";
                        $clienteERP['domicilio']    = "";
                        $clienteERP['codigoPostal'] = "";
                        $clienteERP['poblacion']    = "";
                        $clienteERP['provincia']    = "";
                        $clienteERP['pais']         = "";
                        $clienteERP['telefono1']    = "";
                        $clienteERP['telefono2']    = "";
                        $clienteERP['telefono3']    = "";
                    }

                    ERPClient::Connect()->insertarUsuario($clienteERP);
                }
            }

        }
        catch(PrestaShopWebServiceException $ex)
        {
            echo '<br />'.$ex->getMessage().'<br />';
        }
    }

    public function enviarClienteTiendaAlERP($id, $tarifaWeb)
    {
        try
        {
            $opt = [];
            $opt['resource'] = 'customers';
            $opt['id'] = $id;
            // $opt['display'] = 'full';
            $xml = PrestaShopWS::Connect()->get($opt);
            $cliente = $xml->children()->children();

            $clienteERP = array();
            $clienteERP['user']          = "swdemoaz3";
            $clienteERP['password']      = "swdemoaz3";
            $clienteERP['cadena']        = "bd11g01";
            $clienteERP['idPrestaShop']   = $cliente->id;
            $clienteERP['nombreCompleto'] = $cliente->firstname.' '.$cliente->lastname;
            $clienteERP['email']          = $cliente->email;
            $clienteERP['tarifaWeb']      = $tarifaWeb;

            $opt = [];
            $opt['resource'] = 'addresses';
            $opt['filter[id_customer]'] = '['.$cliente->id.']';
            $opt['display'] = 'full';
            $xml = PrestaShopWS::Connect()->get($opt);
            $direcciones_cliente = $xml->children()->children();
            
            if (!empty($direcciones_cliente))
            {
                $direccion_cliente = $direcciones_cliente[0];

                $pais      = obtenerPais($direccion_cliente->id_country);
                $pais_name = $pais == null ? '' : $pais->name->language[0];
                $provincia = obtenerProvincia($direccion_cliente->id_state);
                $prov_name = $provincia == null ? '' : $provincia->name;

                $clienteERP['nif']          = $direccion_cliente->dni;
                $clienteERP['domicilio']    = $direccion_cliente->address1;
                $clienteERP['codigoPostal'] = $direccion_cliente->postcode;
                $clienteERP['poblacion']    = $direccion_cliente->city;
                $clienteERP['provincia']    = $prov_name;
                $clienteERP['pais']         = $pais_name;
                $clienteERP['telefono1']    = $direccion_cliente->phone;
                $clienteERP['telefono2']    = "";
                $clienteERP['telefono3']    = "";
            }
            else
            {
                $clienteERP['nif']          = "";
                $clienteERP['domicilio']    = "";
                $clienteERP['codigoPostal'] = "";
                $clienteERP['poblacion']    = "";
                $clienteERP['provincia']    = "";
                $clienteERP['pais']         = "";
                $clienteERP['telefono1']    = "";
                $clienteERP['telefono2']    = "";
                $clienteERP['telefono3']    = "";
            }

            $response = ERPClient::Connect()->insertarUsuario($clienteERP);
            echo '<br />USUARIO '.$id.'<br /><pre>';
            print_r($cliente);
            print_r($response);
            print_r($clienteERP);
            echo '</pre><br />';
        }
        catch(PrestaShopWebServiceException $ex)
        {
            echo '<br />'.$ex->getMessage().'<br />';
        }
    }

    public function obtenerClienteDeTienda($id)
    {
        $opt = [];
        // $opt['resource'] = 'customers';
        // $opt['id'] = $id;
        // $xml = PrestaShopWS::Connect()->get($opt);
        $xml = new SimpleXMLElement(file_get_contents(PrestaShopWS::GET_SHOP_URL().'api/customers/'.$id.'?ws_key='.PrestaShopWS::GET_API_KEY()));
        $cliente = $xml->children()->children();

        return $cliente;
    }
}

?>
