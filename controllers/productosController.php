<?php
require_once __DIR__.'/prestashopWS.php';
require_once __DIR__.'/ERPClient.php';
require_once __DIR__.'/utils.php';

define('TAX_RULES_GROUPS', [
    '21' => 1,
    '10' => 2,
    '4' => 3]);


class ProductoERP
{
    public $producto = array();

    public function buildFromStream($input)
    {
        foreach($input as $key => $value)
        {
            $this->producto[$key] = $value;
        }
    }
}

class ProductosController
{
    public function importarProductosERPEnTienda()
    {
        $productosERP = $this->obtenerProductosDeERP();

        foreach($productosERP as $productoERP)
        {
            $productoEnTienda = $this->buscarProductoPorReferenciaEnTienda($productoERP->producto['codigo']);

            if (!$productoEnTienda)
            {
                $this->importarProductoERPEnTienda($productoERP);
            }
            else if ($productoERP->producto['fechaUltModificacion'] > $productoEnTienda->product->date_upd)
            {
                echo 'ERP: '.$productoERP->producto['fechaUltModificacion'].'<br />';
                echo 'PS: '.$productoEnTienda->product->date_upd.'<br />';
                $this->actualizarProductoERPEnTienda($productoEnTienda->product->id, $productoERP);
            }
            else
            {
                echo 'ERP: '.$productoERP->producto['fechaUltModificacion'].'<br />';
                echo 'PS: '.$productoEnTienda->product->date_upd.'<br />';
                echo  "<br />PRODUCTO EXISTENTE EN TIENDA ACTUALIZADO EN UNA FECHA".
                        " POSTERIOR A LA SOLICITADA POR EL ERP. ACTUALIZACION ABORTADA<br /><br />";
            }
        }
    }

    public function importarProductoERPEnTienda($productoERP)
    {
        $categoriasProductosTienda = $this->crearArrayAsociativoDeCategoriasDeTienda();

        $newProduct = null;

        try
        {
            $opt = [];
            $opt['url'] = PrestaShopWS::GET_SHOP_URL.'api/products?schema=blank';
            $blankXml = PrestaShopWS::Connect()->get($opt);
            $product = $blankXml->children()->children();

            $product->name->language[0]              = $productoERP->producto['descripcion'];
            $product->id_category_default            = $categoriasProductosTienda['Inicio'];
            $product->reference                      = $productoERP->producto['codigo'];
            $product->id_manufacturer                = $productoERP->producto['codFabricante'];
            $product->description_short->language[0] = formatHTMLString($productoERP->producto['descAbreviadaWeb']);
            $product->description->language[0]       = formatHTMLString($productoERP->producto['desc_completa']);
            $product->price                          = (float)$productoERP->producto['precio'];
            $product->show_price                     = true;
            $product->state                          = $productoERP->producto['estado'] == 'Activo' ? true : false;
            $product->active                         = $productoERP->producto['estado'] == 'Activo' ? true : false;
            $product->id_tax_rules_group             = TAX_RULES_GROUPS[$productoERP->producto['porcentajeIva']];
            $product->minimal_quantity               = 1;
            $product->available_for_order            = true;

            $productCategories = $product->associations->categories;
            $productCategories->addChild('category')->addChild($categoriasProductosTienda['Inicio']);
            $productCategories->addChild('category')->addChild($categoriasProductosTienda['Productos']);
            $productCategories->addChild('category')->addChild($categoriasProductosTienda[$productoERP->producto['descFamilia']]);

            $opt = [];
            $opt['resource'] = 'products';
            $opt['postXml'] = $blankXml->asXML();
            $xml = PrestaShopWS::Connect()->add($opt);
            $newProduct = $xml->children()->children();

            $this->establecerStockDeProductoEnTienda($newProduct->id, $productoERP);
            $this->importarImagenesDeProductoEnTienda($newProduct->id, $productoERP);
        }
        catch(PrestaShopWebServiceException $ex)
        {
            echo '<br />'.$ex->getMessage().'<br />';
        }
    }

    public function actualizarProductoERPEnTienda($product_id, $productoERP)
    {
        try
        {
            $opt = [];
            $opt['resource'] = 'products';
            $opt['id'] = $product_id;
            $xml = PrestaShopWS::Connect()->get($opt);
            $product = $xml->children()->children();

            unset($product->manufacturer_name);         //mandatory (is read only)
            unset($product->quantity);                  //mandatory (is read only)
            unset($product->id_default_image);          //can cause errors (is not filterable)
            unset($product->id_default_combination);    //can cause errors (is not filterable)
            unset($product->id_tax_rules_group);        //can cause errors (is not filterable)
            unset($product->position_in_category);      //can cause errors (is not filterable)
            unset($product->type);                      //can cause errors (is not filterable)

            $product->name->language[0]              = $productoERP->producto['descripcion'];
            $product->id_category_default            = $categoriasProductosTienda['Inicio'];
            $product->reference                      = $productoERP->producto['codigo'];
            $product->id_manufacturer                = $productoERP->producto['codFabricante'];
            $product->description_short->language[0] = formatHTMLString($productoERP->producto['descAbreviadaWeb']);
            $product->description->language[0]       = formatHTMLString($productoERP->producto['desc_completa']);
            $product->price                          = (float)$productoERP->producto['precio'];
            $product->show_price                     = true;
            $product->state                          = $productoERP->producto['estado'] == 'Activo' ? true : false;
            $product->active                         = $productoERP->producto['estado'] == 'Activo' ? true : false;
            $product->id_tax_rules_group             = TAX_RULES_GROUPS[$productoERP->producto['porcentajeIva']];
            $product->minimal_quantity               = 1;
            $product->available_for_order            = true;

            $opt['putXml'] = $xml->asXML();
            PrestaShopWS::Connect()->edit($opt);

            $this->establecerStockDeProductoEnTienda($product_id, $productoERP);
            $this->importarImagenesDeProductoEnTienda($product_id, $productoERP);
        }
        catch(PrestaShopWebServiceException $ex)
        {
            echo '<br />'.$ex->getMessage().'<br />';
        }
    }

    public function establecerStockDeProductoEnTienda($product_id, $productoERP)
    {
        try
        {
            foreach($product->associations->stock_availables->stock_available as $stock)
            {
                $opt = [];
                $opt['url'] = PrestaShopWS::GET_SHOP_URL().'api/stock_availables?schema=blank';
                $xml = PrestaShopWS::Connect()->get($opt);
                $resources = $xml->children()->children();

                $resources->id                   = $stock->id;
                $resources->id_product           = $product_id;
                $resources->quantity             = $productoERP->producto['stoTotal'];
                $resources->id_shop              = 1;
                $resources->out_of_stock         = 1;
                $resources->depends_on_stock     = 0;
                $resources->id_product_attribute = $stock->id_product_attribute;

                $opt = [];
                $opt['resource'] = 'stock_availables';
                $opt['id'] = $product_id;
                $opt['putXml'] = $xml->asXML();
                PrestaShopWS::Connect()->edit($opt);
            }
        }
        catch(PrestaShopWebServiceException $ex)
        {
            echo '<br />'.$ex->getMessage().'<br />';
        }
    }

    public function importarImagenesDeProductoEnTienda($product_id, $productoERP)
    {
        if ($productoERP->producto['totalImg'] > 0)
        {
            $opt = [];
            $opt['user'] = "swdemoaz3";
            $opt['password'] = "swdemoaz3";
            $opt['cadena'] = 'bd11g01';
            $opt['codProd'] = $productoERP->codigo;
            $imagenes = ERPClient::Connect()->obtenerProductoPSImagenes($opt);

            foreach($imagenes->return as $imagen)
            {
                $img_data  = $imagen->nombreFoto;
                $mime_type = getImageMimeType($img_data);

                $tmp_file = fopen('tmpimg.'.$mime_type, 'w');
                fwrite($tmp_file, $img_data);
                fclose($tmp_file);

                $args = [];
                $args['image'] = new CurlFile('tmpimg.'.$mime_type, 'image/'.$mime_type);
                
                $curl_handle = curl_init();
                curl_setopt($curl_handle, CURLOPT_HEADER, 1);
                curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl_handle, CURLINFO_HEADER_OUT, 1);
                curl_setopt($curl_handle, CURLOPT_URL, PrestaShopWS::GET_SHOP_URL()."api/images/products/".$product_id."/");
                curl_setopt($curl_handle, CURLOPT_POST, 1);
                curl_setopt($curl_handle, CURLOPT_USERPWD, PrestaShopWS::GET_API_KEY().':');
                curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $args);

                $result    = curl_exec($curl_handle);
                $http_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
                curl_close($curl_handle);

                if (200 == $http_code)
                {
                    echo '<br />Imagen de producto ('.$product_id.') importada correctamente.';
                }
                else
                {
                    echo "<br />".$http_code.': Error uploading product image (ID: '.$product_id.")";
                }
            }
        }
    }

    public function crearArrayAsociativoDeCategoriasDeTienda()
    {
        $categoriasArray = array();

        try
        {
            $opt = [];
            $opt['resource'] = 'categories';
            $xml = PrestaShopWS::Connect()->get($opt);
            $categorias_ids = $xml->children()->children();

            foreach($categorias_ids as $item)
            {
                $opt['id'] = $item->attributes()->id;
                $xml = PrestaShopWS::Connect()->get($opt);
                $categoria = $xml->children()->children();

                $categoriasArray[(string)$categoria->name->language[0]] = $categoria->id;
            }
        }
        catch(PrestaShopWebServiceException $ex)
        {
            echo '<br />'.$ex->getMessage().'<br />';
        }
        finally
        {
            return $categoriasArray;
        }
    }

    public function obtenerProductosDeTienda()
    {
        try
        {
            $opt = [];
            $opt['resource'] = 'products';
            $xml = PrestaShopWS::Connect()->get($opt);
            $products_ids = $xml->children()->children();

            foreach($products_ids as $item)
            {
                $opt['id'] = $item->attributes()->id;
                $xml = PrestaShopWS::Connect()->get($opt);
                $product = $xml->children()->children();

                echo '<pre>';
                print_r($product);
            }
        }
        catch(PrestaShopWebServiceException $ex)
        {
            echo '<br />'.$ex->getMessage().'<br />';
        }
    }

    public function obtenerProductoDeTienda($product_id)
    {
        $product = null;

        try
        {
            $opt = [];
            $opt['resource'] = 'products';
            $opt['id'] = $product_id;
            $xml = PrestaShopWS::Connect()->get($opt);
            $product = $xml->children()->children();
        }
        catch(PrestaShopWebServiceException $ex)
        {
            echo '<br />'.$ex->getMessage().'<br />';
        }
        finally
        {
            return $product;
        }
    }

    public function buscarProductoPorReferenciaEnTienda($referencia)
    {
        $xml = null;
        try
        {
            $opt = array();
            $opt['resource'] = 'products';
            $opt['filter[reference]'] = $referencia;
            $opt['display'] = 'full';
            $xml = PrestaShopWS::Connect()->get($opt);

            $xml = (count($xml->children()->children()) == 0) ? null : $xml->children()->children();
        }
        catch(PrestaShopWebServiceException $ex)
        {
            echo '<br />'.$ex->getMessage().'<br />';
        }
        finally
        {
            return $xml;
        }
    }

    public function obtenerProductosDeERP(): array
    {
        $args = [];
        $args['user'] = "swdemoaz3";
        $args['password'] = "swdemoaz3";
        $args['cadena'] = "bd11g01";
        $args['codCliente'] = 4000;
        $args['fechaActual'] = date("d/m/Y");
        $response = ERPClient::Connect()->obtenerProductosPS($args);
        
        $productosERP = array();
        if (!empty($response->return))
        {
            foreach($response->return as $item)
            {
                $productoERP = new ProductoERP();
                $productoERP->buildFromStream($item);

                $productosERP[] = $productoERP;
            }
        }

        return $productosERP;
    }
}

?>
