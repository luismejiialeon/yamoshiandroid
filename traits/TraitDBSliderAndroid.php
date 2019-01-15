<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 08/01/2019
 * Time: 6:41
 */

trait TraitDBSliderAndroid
{

    /**
     * Creates tables
     */
    protected function createTablesSlider()
    {
        /* Slides */
        $res = (bool)Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'androidslider` (
                `id_androidslider_slides` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `id_shop` int(10) unsigned NOT NULL,
                PRIMARY KEY (`id_androidslider_slides`, `id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');

        /* Slides configuration */
        $res &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'androidslider_slides` (
              `id_androidslider_slides` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `position` int(10) unsigned NOT NULL DEFAULT \'0\',
              `active` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
              PRIMARY KEY (`id_androidslider_slides`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');

        /* Slides lang configuration */
        $res &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'androidslider_slides_lang` (
              `id_androidslider_slides` int(10) unsigned NOT NULL,
              `id_lang` int(10) unsigned NOT NULL,
              `title` varchar(255) NOT NULL,
              `description` text NOT NULL,
              `legend` varchar(255) NOT NULL,
              `url` varchar(255) NOT NULL,
              `image` varchar(255) NOT NULL,
              PRIMARY KEY (`id_androidslider_slides`,`id_lang`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');

        return $res;
    }

    /**
     * deletes tables
     */
    protected function deleteTablesSlider()
    {
        $slides = $this->getSlides();
        foreach ($slides as $slide) {
            $to_del = new AndroidSlider($slide['id_slide']);
            $to_del->delete();
        }

        return Db::getInstance()->execute('
            DROP TABLE IF EXISTS 
              `' . _DB_PREFIX_ . 'androidslider`, 
              `' . _DB_PREFIX_ . 'androidslider_slides`, 
              `' . _DB_PREFIX_ . 'androidslider_slides_lang`;
        ');
    }


    public function getNextPositionSlider()
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
            SELECT MAX(hss.`position`) AS `next_position`
            FROM `' . _DB_PREFIX_ . 'androidslider_slides` hss, `' . _DB_PREFIX_ . 'androidslider` hs
            WHERE hss.`id_androidslider_slides` = hs.`id_androidslider_slides` AND hs.`id_shop` = ' . (int)$this->context->shop->id
        );

        return (++$row['next_position']);
    }

    public function getSlides($active = null)
    {
        $this->context = Context::getContext();
        $id_shop = $this->context->shop->id;
        $id_lang = $this->context->language->id;

        $slides = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT hs.`id_androidslider_slides` as id_slide, hss.`position`, hss.`active`, hssl.`title`,
            hssl.`url`, hssl.`legend`, hssl.`description`, hssl.`image`
            FROM ' . _DB_PREFIX_ . 'androidslider hs
            LEFT JOIN ' . _DB_PREFIX_ . 'androidslider_slides hss ON (hs.id_androidslider_slides = hss.id_androidslider_slides)
            LEFT JOIN ' . _DB_PREFIX_ . 'androidslider_slides_lang hssl ON (hss.id_androidslider_slides = hssl.id_androidslider_slides)
            WHERE id_shop = ' . (int)$id_shop . '
            AND hssl.id_lang = ' . (int)$id_lang .
            ($active ? ' AND hss.`active` = 1' : ' ') . '
            ORDER BY hss.position'
        );

        foreach ($slides as &$slide) {
            $slide['image_url'] = $this->context->link->getMediaLink(_MODULE_DIR_ . 'yamoshiandroid/images/' . $slide['image']);
            $slide['url'] = $this->updateUrl($slide['url']);
        }

        return $slides;
    }

    public function getAllImagesBySlidesId($id_slides, $active = null, $id_shop = null)
    {
        $this->context = Context::getContext();
        $images = array();

        if (!isset($id_shop))
            $id_shop = $this->context->shop->id;

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT hssl.`image`, hssl.`id_lang`
            FROM ' . _DB_PREFIX_ . 'androidslider hs
            LEFT JOIN ' . _DB_PREFIX_ . 'androidslider_slides hss ON (hs.id_androidslider_slides = hss.id_androidslider_slides)
            LEFT JOIN ' . _DB_PREFIX_ . 'androidslider_slides_lang hssl ON (hss.id_androidslider_slides = hssl.id_androidslider_slides)
            WHERE hs.`id_androidslider_slides` = ' . (int)$id_slides . ' AND hs.`id_shop` = ' . (int)$id_shop .
            ($active ? ' AND hss.`active` = 1' : ' ')
        );

        foreach ($results as $result)
            $images[$result['id_lang']] = $result['image'];

        return $images;
    }
    public function slideExists($id_slide)
    {
        $req = 'SELECT hs.`id_androidslider_slides` as id_slide
                FROM `' . _DB_PREFIX_ . 'androidslider` hs
                WHERE hs.`id_androidslider_slides` = ' . (int)$id_slide;
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($req);

        return ($row);
    }
    public function DBActionShopDataDuplication($params)
    {
        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'androidslider (id_androidslider_slides, id_shop)
            SELECT id_androidslider_slides, ' . (int)$params['new_id_shop'] . '
            FROM ' . _DB_PREFIX_ . 'androidslider
            WHERE id_shop = ' . (int)$params['old_id_shop']
        );
    }
    public function updateUrl($link)
    {
        if (substr($link, 0, 7) !== "http://" && substr($link, 0, 8) !== "https://") {
            $link = "http://" . $link;
        }

        return $link;
    }

}
