<?php
/*
* 2007-2015 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since   1.5.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use App\Android\AndroidSchema;
use App\Database\Schema\Blueprint;

include_once(_PS_MODULE_DIR_ . 'yamoshiandroid/vendor/autoload.php');
include_once(_PS_MODULE_DIR_ . 'yamoshiandroid/vendor/customautoload.php');

class YamoshiAndroid extends AndroidBaseModule /*implements WidgetInterface*/
{
    USE TraitDBSliderAndroid, RouterTrait, TraitDBCategoriesAndroid;
    protected $tabs = array(
        array(
            'name' => 'Android Studio', // One name for all langs
            'class_name' => 'AdminYamoshiandroid',
            'visible' => true,
            'parent_class_name' => 'IMPROVE',
        ), array(
            'name' => 'Seleccionar Categorías', // One name for all langs
            'class_name' => 'AdminAndroidCategories',
            'visible' => true,
            'parent_class_name' => 'AdminYamoshiandroid',
        ), array(
            'name' => 'Android Slider', // One name for all langs
            'class_name' => 'AdminAndroidSldier',
            'visible' => true,
            'parent_class_name' => 'AdminYamoshiandroid',
        ));

    protected $register_hooks = [
       // "displayHeader",
        "moduleRoutes",
        "actionShopDataDuplication"
    ];
    protected $register_configuration_keys = [
        "ANDROIDMANAGER_CATEGORY_COUNT" => 3,
        "ANDROIDMANAGER_CATEGORY_LIMIT" => 20,
        "ANDROIDSLIDER_SPEED" => 5000,
        "ANDROIDSLIDER_PAUSE_ON_HOVER" => 1,
        "ANDROIDSLIDER_WRAP" => 1,
    ];


    protected $_html = '';
    protected $templateFile;

    public function __construct()
    {
        $this->name = 'yamoshiandroid';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Luis Mejia';
        $this->need_instance = 0;
        $this->secure_key = Tools::hash($this->name);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->getTranslator()->trans('Android Manager', array(), 'Modules.AndroidSlide.Admin');
        $this->description = $this->getTranslator()->trans('Administre su aplicacion android.', array(), 'Modules.AndroidSlide.Admin');
        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);


        $this->templateFile = 'module:yamoshiandroid/views/templates/hook/sliders.tpl';
    }

    /**
     * @see Module::install()
     */
    public function install()
    {
        /* Adds Module */
        if (/*$this->installTab("YamoshiandroidController", $this->displayName, "IMPROVE") &&*/

        parent::install()

            //$this->registerHook('displayHome') &&
        ) {

            /* Adds samples */
            $this->installSamples();

            // Disable on mobiles and tablets
            $this->disableDevice(Context::DEVICE_MOBILE);

            return true;
        }

        return false;
    }

    /**
     * Adds samples
     */
    protected function installSamples()
    {
        $languages = Language::getLanguages(false);
        for ($i = 1; $i <= 3; ++$i) {
            $slide = new AndroidSlider();
            $slide->position = $i;
            $slide->active = 1;
            foreach ($languages as $language) {
                $slide->title[$language['id_lang']] = 'Sample ' . $i;
                $slide->description[$language['id_lang']] = '<h2>EXCEPTEUR OCCAECAT</h2>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin tristique in tortor et dignissim. Quisque non tempor leo. Maecenas egestas sem elit</p>';
                $slide->legend[$language['id_lang']] = 'sample-' . $i;
                $slide->url[$language['id_lang']] = 'http://www.prestashop.com/?utm_source=back-office&utm_medium=v17_androidslider'
                    . '&utm_campaign=back-office-' . Tools::strtoupper($this->context->language->iso_code)
                    . '&utm_content=' . (defined('_PS_HOST_MODE_') ? 'ondemand' : 'download');
                $slide->image[$language['id_lang']] = 'sample-' . $i . '.jpg';
            }
            $slide->add();
        }
    }

    /**
     * @see Module::uninstall()
     */
    public function uninstall()
    {
        return parent::uninstall()/* && $this->uninstallTab("AdminYamoshiandroid")*/
            ;
    }

    /**
     * @return bool
     */
    public function onCreateDatabaseTable()
    {
        return AndroidSchema::create("yamoshiandroid_categories", function (Blueprint $blueprint) {
                //$blueprint->dropIfExists();
                $blueprint->increments("id_yamoshiandroid_categories");
                $blueprint->unsignedInteger("id_category");
                $blueprint->unsignedInteger("id_shop");
                $blueprint->unsignedInteger("position")->default('0');
                $blueprint->boolean("active")->default('1');
            })
            && AndroidSchema::deletePrimaryKeyAndAdd('yamoshiandroid_categories',['id_yamoshiandroid_categories', 'id_category', 'id_shop'])

            && AndroidSchema::create("androidslider", function (Blueprint $blueprint) {
                //$blueprint->dropIfExists();
                $blueprint->increments("id_androidslider_slides");
                $blueprint->unsignedInteger("id_shop")->nullable();
            })
            && AndroidSchema::deletePrimaryKeyAndAdd('androidslider',['id_androidslider_slides', 'id_shop'])
            && AndroidSchema::create("androidslider_slides", function (Blueprint $blueprint) {
                //$blueprint->dropIfExists();
                $blueprint->increments("id_androidslider_slides");
                $blueprint->unsignedInteger("position")->default('0');
                $blueprint->boolean("active")->default('1');
            })
            && AndroidSchema::create("androidslider_slides_lang", function (Blueprint $blueprint) {
                //$blueprint->dropIfExists();
                $blueprint->increments("id_androidslider_slides");
                $blueprint->unsignedInteger("id_lang");
                $blueprint->string("title");
                $blueprint->text("description");
                $blueprint->string("legend", 255);
                $blueprint->string("url", 255);
                $blueprint->string("image", 255);
            })
            &&
            AndroidSchema::deletePrimaryKeyAndAdd('androidslider_slides_lang',['id_androidslider_slides', 'id_lang']);

    }

    /**
     * @return bool
     */
    public function onDeleteDatabaseTable()
    {
        return AndroidSchema::deleteTable("yamoshiandroid_categories")
            && AndroidSchema::deleteTable("androidslider")
            && AndroidSchema::deleteTable("androidslider_slides")
            && AndroidSchema::deleteTable("androidslider_slides_lang");
    }
/*
    public function hookdisplayHeader($params)
    {
        $this->context->controller->registerStylesheet('modules-androidslider', 'modules/' . $this->name . '/css/androidslider.css', ['media' => 'all', 'priority' => 150]);
        $this->context->controller->registerJavascript('modules-responsiveslides', 'modules/' . $this->name . '/js/responsiveslides.min.js', ['position' => 'bottom', 'priority' => 150]);
        $this->context->controller->registerJavascript('modules-androidslider', 'modules/' . $this->name . '/js/androidslider.js', ['position' => 'bottom', 'priority' => 150]);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId())) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $this->getCacheId());
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $slides = $this->getSlides(true);
        if (is_array($slides)) {
            foreach ($slides as &$slide) {
                $slide['sizes'] = @getimagesize((dirname(__FILE__) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $slide['image']));
                if (isset($slide['sizes'][3]) && $slide['sizes'][3]) {
                    $slide['size'] = $slide['sizes'][3];
                }
            }
        }

        $config = $this->getConfigFieldsValues();

        return [
            'androidslider' => [
                'speed' => $config['ANDROIDSLIDER_SPEED'],
                'pause' => $config['ANDROIDSLIDER_PAUSE_ON_HOVER'] ? 'hover' : '',
                'wrap' => $config['ANDROIDSLIDER_WRAP'] ? 'true' : 'false',
                'slides' => $slides,
            ],
        ];
    }
*/


    public function clearCache()
    {
        $this->_clearCache($this->templateFile);
    }

    public function hookActionShopDataDuplication($params)
    {
        $this->DBActionShopDataDuplication($params);
        $this->clearCache();
    }



    public function getConfigFieldsValues()
    {
        $id_shop_group = Shop::getContextShopGroupID();
        $id_shop = Shop::getContextShopID();

        return array(
            'ANDROIDSLIDER_SPEED' => Tools::getValue('ANDROIDSLIDER_SPEED', Configuration::get('ANDROIDSLIDER_SPEED', null, $id_shop_group, $id_shop)),
            'ANDROIDSLIDER_PAUSE_ON_HOVER' => Tools::getValue('ANDROIDSLIDER_PAUSE_ON_HOVER', Configuration::get('ANDROIDSLIDER_PAUSE_ON_HOVER', null, $id_shop_group, $id_shop)),
            'ANDROIDSLIDER_WRAP' => Tools::getValue('ANDROIDSLIDER_WRAP', Configuration::get('ANDROIDSLIDER_WRAP', null, $id_shop_group, $id_shop)),
        );
    }


}
