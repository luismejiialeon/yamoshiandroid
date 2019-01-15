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

class AndroidCategory extends ObjectModel
{
    public $title;
    public $description;
    public $url;
    public $legend;
    public $image;
    public $active;
    public $position;
    public $id_shop;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'yamoshiandroid_categories',
        'primary' => 'id_yamoshiandroid_categories',
        'multilang' => false,
        'fields' => array(
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'position' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'id_category' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
        )
    );

    /**
     * AndroidCategory constructor.
     * @param null $id_android
     * @param null $id_lang
     * @param null $id_shop
     * @param Context|null $context
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id_android = null, $id_lang = null, $id_shop = null, Context $context = null)
    {
        parent::__construct($id_android, $id_lang, $id_shop);
    }



    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function delete()
    {
        $res = true;
        $res &= $this->reOrderPositions();
        $res &= parent::delete();
        return $res;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function reOrderPositions()
    {
        $id_android = $this->id;
        $max = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT MAX(`position`) as position
			FROM `' . _DB_PREFIX_ . 'yamoshiandroid_categories`'
        );

        if ((int)$max == (int)$id_android)
            return true;

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT `position`, `id_yamoshiandroid_categories` as id_android, `id_category`
			FROM `' . _DB_PREFIX_ . 'yamoshiandroid_categories`
			WHERE `position` > ' . (int)$this->position
        );

        foreach ($rows as $row) {
            $current_slide = new AndroidCategory($row['id_android']);
            $current_slide->id_category=$row['id_category'];
            --$current_slide->position;
            $current_slide->update();
            unset($current_slide);
        }

        return true;
    }

}
