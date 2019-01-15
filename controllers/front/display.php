<?php
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
        $std->PS_SHOP_ENABLE = Tools::boolVal(Configuration::get('PS_SHOP_ENABLE') ?: false);
        $std->PS_MAINTENANCE_IP = Configuration::get('PS_MAINTENANCE_IP');
        $std->PS_MAINTENANCE_TEXT =
            Configuration::get('PS_MAINTENANCE_TEXT', (int)$this->context->language->id);
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

    public function addcartRoute()
    {
        return "daasd";
    }
}
