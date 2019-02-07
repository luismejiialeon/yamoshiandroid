<?php

use App\AndroidCustomer;

/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
class  yamoshiandroidDisplayModuleFrontController extends AndroidBaseController
{
    use TraitDBCategoriesAndroid;
    protected $exclude_maintenance = [
        "indexRoute", "categoriesRoute", "slidersRoute", "imagesliderRoute"
    ];

    public function getCategoriesByApi($ids = array(), $params = array())
    {
        $class_name = WebserviceKey::getClassFromKey($this->wsKey);
        $bad_class_name = false;
        if (!class_exists($class_name)) {
            $bad_class_name = $class_name;
            $class_name = 'WebserviceRequest';
        }

        WebserviceRequest::$ws_current_classname = $class_name;
        $request = WebserviceRequest::getInstance();
        $result = $request->fetch($this->wsKey, "GET", "categories", array_merge([
            "display" => "full",
            "filter" => [
                "active" => "1",
                "id" => "[" . implode("|", $ids) . "]"
            ]
        ], $params), $bad_class_name, null);
        return $result;
    }

    function getConfigFormCategoriesValues()
    {
        $limit = Configuration::get('ANDROIDMANAGER_CATEGORY_LIMIT');
        if ($limit == null) {
            Configuration::updateValue('ANDROIDMANAGER_CATEGORY_LIMIT', 3);
            $limit = 3;
        }
        $limit = intval($limit);
        if ($limit <= 0) {
            Configuration::updateValue('ANDROIDMANAGER_CATEGORY_LIMIT', 3);
            $limit = 3;
        }
        $cats_keys = [];
        for ($i = 1; $i <= $limit; $i++) {
            $value = Configuration::get("ANDROIDMANAGER_CATEGORY_N$i");
            if (!!$value) {
                $cats_keys["ANDROIDMANAGER_CATEGORY_N$i"] = $value;
            }
        }
        return $cats_keys;
    }


    /**
     * @return array|false|mysqli_result|PDOStatement|resource|null
     * @throws PrestaShopDatabaseException
     */
    protected function getSliders()
    {
        $context = Context::getContext();
        $id_shop = $context->shop->id;
        $result = DB::getInstance(_PS_USE_SQL_SLAVE_)
            ->executeS('SELECT h.id_androidslider_slides, h.id_shop,hs.position,hs.active,hsl.title,hsl.description,hsl.legend,hsl.url,hsl.image 
                    FROM `' . _DB_PREFIX_ . 'androidslider` h 
                    INNER join `' . _DB_PREFIX_ . 'androidslider_slides` hs 
                    on h.id_androidslider_slides=hs.id_androidslider_slides
                    INNER join `' . _DB_PREFIX_ . 'androidslider_slides_lang` hsl
                    on h.id_androidslider_slides=hsl.id_androidslider_slides
                    WHERE hs.active=1 and h.`id_shop` = ' . (int)$id_shop . ' and hsl.id_lang=' . $context->language->id . ' 
                    order by hs.position asc
'
            );

        foreach ($result as $key => $r) {
            $slider = (object)$r;
            $slider->id_androidslider_slides = (integer)$slider->id_androidslider_slides;
            $slider->id_shop = (integer)$slider->id_shop;
            $slider->position = (integer)$slider->position;
            $slider->active = (bool)$slider->active;
            $slider->image_url = $this->context
                ->link
                ->getMediaLink(_MODULE_DIR_ . 'yamoshiandroid/images/' . $slider->image);
            $result[$key] = $slider;
        }
        return $result;

    }


    public function indexRoute()
    {
        $std = new stdClass();
        $std->ID_LANG = (int)(Configuration::get('PS_LANG_DEFAULT') ?: $this->context->language->id);
        $std->ID_SHOP = (int)$this->context->shop->id;
        $std->ID_SHOP_GROUP = (int)$this->context->shop->id_shop_group;
        $std->ID_CURRENCY = (int)Configuration::get("ANDROIDMANAGER_CURRENCY_DEFAULT");
        $std->ID_CARRIER_DEFAULT = (int)Configuration::get('PS_CARRIER_DEFAULT', (int)$std->ID_LANG);
        $std->ID_COUNTRY_DEFAULT = (int)Configuration::get('PS_COUNTRY_DEFAULT', (int)$std->ID_LANG);
        $std->ID_ZONE_DEFAULT = Country::getIdZone($std->ID_COUNTRY_DEFAULT);

        $std->PS_TAX_ENABLE = (bool)((int)Configuration::get('PS_TAX'));

        $std->PS_SHOP_ENABLE = Tools::boolVal(Configuration::get('PS_SHOP_ENABLE') ?: false);
        $std->PS_MAINTENANCE_IP = Configuration::get('PS_MAINTENANCE_IP');
        $std->PS_MAINTENANCE_TEXT = Configuration::get('PS_MAINTENANCE_TEXT', (int)$std->ID_LANG);


        $std->PS_SHIPPING_HANDLING = (float)Configuration::get('PS_SHIPPING_HANDLING', (int)$std->ID_LANG);
        $std->PS_SHIPPING_FREE_PRICE = (float)Configuration::get('PS_SHIPPING_FREE_PRICE', (int)$std->ID_LANG);
        $std->PS_SHIPPING_FREE_WEIGHT = (float)Configuration::get('PS_SHIPPING_FREE_WEIGHT', (int)$std->ID_LANG);


        $std->CARGOS_CARD_PAYMENT = (float)Configuration::get('ANDROIDMANAGER_CARGOS_CARD_PAYMENT');
        $std->CARGOS_CARD_PAYMENT = ($std->CARGOS_CARD_PAYMENT / 100);
        $std->CUENTAS_BANCO = base64_decode(Configuration::get('ANDROIDMANAGER_INFO_BANCARIA'));
        $std->CULQI_ACTIVE = (bool)Configuration::get("ANDROIDMANAGER_ALLOW_PAYMENT_CARD");
        $std->CULQI_PRIVATE_KEY = Configuration::get("ANDROIDMANAGER_CULQI_PRIVATE_KEY") ?: "";
        $std->CULQI_PUBLIC_KEY = Configuration::get("ANDROIDMANAGER_CULQI_PUBLIC_KEY") ?: "";
        if (empty($std->CULQI_PRIVATE_KEY) || empty($std->CULQI_PUBLIC_KEY)) {
            $std->CULQI_ACTIVE = false;
        }
        return $std;
    }

    function getProductsWs($id_category)
    {
        $limit = (int)Configuration::get("ANDROIDMANAGER_CATEGORY_LIMIT");
        if ($limit <= 0) {
            $limit = 20;
        }
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT cp.`id_product` as id
		FROM `' . _DB_PREFIX_ . 'category_product` cp
		WHERE cp.`id_category` = ' . (int)$id_category . '
		ORDER BY `position` ASC LIMIT ' . $limit);
    }

    public function categoriesRoute()
    {
        $categories = [];
        foreach ($this->getCategories(true) as $key => $value) {
            $cat = new Category($value['id_category'], $this->context->language->id);
            $cat->associations = [
                "products" => $this->getProductsWs($value['id_category'])
            ];
            $categories[] = $cat;
        }
        //$params = $_GET;
        //unset($params['url']);
        //$result = self::getCategoriesByApi($ids, $params);
        $result = $categories;
        /*if (is_array($result['headers'])) {
            foreach ($result['headers'] as $param_value) {
                header($param_value);
            }
        }*/
        //header("Content-type:application/json");
        return $result;
        //return response($result['content'], 200, $result['headers']);
    }

    /**
     * @return stdClass
     * @throws PrestaShopDatabaseException
     */
    public function slidersRoute()
    {
        return $this->imagesliderRoute();
    }

    /**
     * @return stdClass
     * @throws PrestaShopDatabaseException
     */
    public function imagesliderRoute()
    {

        $std = new stdClass();
        $std->androidsliders = [];
        $std->SLIDER_SPEED = (integer)Configuration::get('ANDROIDSLIDER_SPEED');
        $std->androidsliders = $this->getSliders();
        return $std;
    }


    /**
     * @param $paths
     * @param $query
     * @param $raw
     * @return array
     * @throws PrestaShopException
     */
    public function authRoute($paths, $query, $raw)
    {
        $query = collect($query);
        $query = $query->merge($raw);
        switch ($paths[0]) {
            case "login":
                $email = $query->get("email");
                $passwd = $query->get("passwd");
                $customer = new AndroidCustomer();
                return $customer->Login($email, $passwd);
            case "update":
                $email = $query->get("email");
                $passwd = $query->get("passwd");
                $customer = new AndroidCustomer($query);
                $c = $customer->Login($email, $passwd);
                if ($c["login"]) {
                    return $customer->update($c["customer"]['id_customer']);
                }
                return $customer->createResponse(false, $c["customer"]);
            case "register":
                $group = Group::getGroups($this->context->language->id);
                $group = collect($group);
                $group = $group->first(function ($item) {
                    return strtolower($item["name"] ?: '') == "client" || strtolower($item["name"] ?: '') == "cliente";
                });
                $validation = collect(array(
                    "id_default_group" => $group['id_group'] ?: 3,
                    "id_lang" => $this->context->language->id,
                    "lastname" => '',
                    "firstname" => '',
                    "email" => '',
                    "id_gender" => 0,
                    "optin" => 0,
                    "newsletter" => 0,
                    "active" => 1,
                    "is_guest" => 0,
                    "id_shop" => $this->context->shop->id,
                    "id_shop_group" => $this->context->shop->id_shop_group));

                $validation = $validation->map(function ($index, $key) use ($query, $validation) {
                    return $query->has($key) ? $query->get($key) : $validation->get($key);
                });
                $validation->put("passwd", $query->get("passwd"));
                $customer = new AndroidCustomer($validation);
                return $customer->create();
        }
        return [];
    }

    /**
     * @param $paths
     * @return Address|\App\Android\AndroidResponse|array|Customer
     * @throws PrestaShopException
     */
    public function customerRoute($paths)
    {
        if (isset($paths[0]) && is_numeric($paths[0])) {

            $customer = new Customer($paths[0]);
            if (isset($paths[1]) && $paths[1] == "address") {
                $address = Address::initialize(Address::getFirstCustomerAddressId($customer->id) ?: null);

                if (request()->method() == "GET") {
                    if (Validate::isLoadedObject($address)) {
                        return $address;
                    }
                } elseif (request()->method() == "POST" || request()->method() == "PUT") {
                    return array("result" => request()->isJson());
                }
            }
        }
        return self::__not_found();
    }

    public function addOderRoute()
    {
        $request = request();
        if ($request->has("module") && $payment_module = Module::getInstanceByName($request->module)) {
            if ($request->has("id_customer")) {
                $customer = new Customer($request->id_customer);
                if ($request->has("id_cart") && $request->has("total_paid") && $request->has("payment")) {
                    $mailVars = array(
                        '{check_name}' => Configuration::get('CHEQUE_NAME'),
                        '{check_address}' => Configuration::get('CHEQUE_ADDRESS'),
                        '{check_address_html}' => str_replace("\n", '<br />', Configuration::get('CHEQUE_ADDRESS')));

                    $created_order = $payment_module->validateOrder($request->id_cart,
                        Configuration::get('PS_OS_CHEQUE')
                        , $request->total_paid, $request->payment, null, $mailVars, null, false, $customer->secure_key);
                    if ($created_order) {
                        return response(new Order($payment_module->currentOrder), 200);
                    } else {
                        return response("No se pudo crear la orden", 402);
                    }
                } else {
                    return response("Falta los datos de alguno de estos atributos[id_cart,total_paid,payment]", 400);
                }
            } else {
                return response("Palta el id del cliente", 400);
            }
        } else {
            return response("El modulo de pago no existe", 400);
        }
    }


    public function demoRoute($a)
    {
        return [$a,$_GET];
    }
}
