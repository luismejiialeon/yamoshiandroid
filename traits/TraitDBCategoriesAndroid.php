<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 08/01/2019
 * Time: 6:41
 */

trait TraitDBCategoriesAndroid
{


    /**
     * Creates tables
     */
    protected function createCategoriesTables()
    {
        /////////////////////////////////////
        $res = Db::getInstance()->execute('
        CREATE TABLE `' . _DB_PREFIX_ . 'yamoshiandroid_categories` (
            `id_yamoshiandroid_categories` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_category` INT(10) UNSIGNED NOT NULL,
            `id_shop` INT(10) UNSIGNED NOT NULL,
            `position` INT(10) UNSIGNED NOT NULL DEFAULT \'0\',
            `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\',
            PRIMARY KEY (`id_yamoshiandroid_categories`, `id_category`, `id_shop`)
        )ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');

        return $res;
    }

    /**
     * deletes tables
     */
    protected function deleteCategoriesTables()
    {
        $slides = $this->getCategories();
        foreach ($slides as $slide) {
            $to_del = new AndroidCategory($slide['id_android']);
            $to_del->delete();
        }

        return Db::getInstance()->execute('
            DROP TABLE IF EXISTS 
              `' . _DB_PREFIX_ . 'yamoshiandroid_categories`;
        ');
    }


    public function getNextPositionCategory()
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
            SELECT MAX(hs.`position`) AS `next_position`
            FROM `' . _DB_PREFIX_ . 'yamoshiandroid_categories` hs'
        );

        return (++$row['next_position']);
    }

    public function getCategories($active = null)
    {
        $this->context = Context::getContext();
        $slides = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT `id_yamoshiandroid_categories` as id_android, `position`, `active`, `id_category`
            FROM ' . _DB_PREFIX_ . 'yamoshiandroid_categories
            WHERE ' .
            ($active ? ' `active` = 1' : ' ') . '
            ORDER BY position asc'
        );

        return $slides;
    }

    public function getAllCategoriesByDbId($id_android, $active = null)
    {
        $this->context = Context::getContext();
        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT hss.`id_category`
            FROM ' . _DB_PREFIX_ . 'yamoshiandroid_categories hs
            WHERE hs.`id_yamoshiandroid_categories` = ' . (int)$id_android . '  ' .
            ($active ? ' AND hs.`active` = 1' : ' ')
        );
        return $results;
    }

    public function categoryExists($id_android)
    {
        $req = 'SELECT hs.`id_yamoshiandroid_categories` as id_android
                FROM `' . _DB_PREFIX_ . 'yamoshiandroid_categories` hs
                WHERE hs.`id_yamoshiandroid_categories` = ' . (int)$id_android;
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($req);

        return ($row);
    }

    protected function getConfigFormValuesCategory()
    {
        return array_merge([
            'ANDROIDMANAGER_CATEGORY_COUNT' => Configuration::get('ANDROIDMANAGER_CATEGORY_COUNT'),
            'ANDROIDMANAGER_CATEGORY_LIMIT' => Configuration::get('ANDROIDMANAGER_CATEGORY_LIMIT'),
        ]);
    }

    public function getSelectedCategories()
    {
        $cats = $this->getCategories(true);
        $ids = [];
        foreach ($cats as $cat) {
            $ids[] = $cat['id_category'];
        }
        return $ids;
    }

    public function getSelectedCategoriesWithId()
    {
        $cats = $this->getCategories(true);
        $ids = [];
        foreach ($cats as $cat) {
            $ids[] = [
                "id" => $cat['id_android'],
                "id_category" => $cat['id_category']
            ];
        }
        return $ids;
    }
}
