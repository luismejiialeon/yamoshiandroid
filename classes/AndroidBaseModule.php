<?php


/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 10/01/2019
 * Time: 11:56
 */
abstract class AndroidBaseModule extends Module
{
    protected $register_hooks = [];
    protected $register_configuration_keys = [];

    public function install()
    {
        $res = true;

        if (parent::install() ) {
            foreach ($this->register_hooks as $hook) {
                $res &= $this->registerHook($hook);
            }
            $shops = Shop::getContextListShopID();
            $shop_groups_list = array();

            /* Setup each shop */
            foreach ($shops as $shop_id) {
                $shop_group_id = (int)Shop::getGroupFromShop($shop_id, true);

                if (!in_array($shop_group_id, $shop_groups_list)) {
                    $shop_groups_list[] = $shop_group_id;
                }
                foreach ($this->register_configuration_keys as $key_config => $defvalue) {
                    $res &= Configuration::updateValue($key_config, $defvalue, false, $shop_group_id, $shop_id);
                }
            }

            /* Sets up Shop Group configuration */
            if (count($shop_groups_list)) {
                foreach ($shop_groups_list as $shop_group_id) {
                    foreach ($this->register_configuration_keys as $key_config => $defvalue) {
                        $res &= Configuration::updateValue($key_config, $defvalue, false, $shop_group_id);
                    }
                }
            }

            /* Sets up Global configuration */
            foreach ($this->register_configuration_keys as $key_config => $defvalue) {
                $res &= Configuration::updateValue($key_config, $defvalue);
            }
            $res &= $this->onCreateDatabaseTable();

        };
        return $res;
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        $res = true;
        foreach ($this->register_configuration_keys as $key_config => $defvalue) {
            $res &= Configuration::deleteByName($key_config);
        }
        return $res &&  $this->onDeleteDatabaseTable() && parent::uninstall();
    }


    protected function createBluePrint(Closure $callback)
    {
        $blueprint = new \App\Database\Schema\Blueprint("", function (\App\Database\Schema\Blueprint $blueprint) use ($callback) {
            $blueprint->create();
            $callback($blueprint);
        });
        return $blueprint;
    }

    /**
     * Delete module from datable
     *
     * @return bool result
     */
    public abstract function onCreateDatabaseTable();

    /**
     * Delete module from datable
     *
     * @return bool result
     */
    public abstract function onDeleteDatabaseTable();


    public function installTab($yourControllerClassName, $yourTabName, $tabParentControllerName = false)
    {
        $tab = new \Tab();
        $tab->active = 1;
        $tab->class_name = $yourControllerClassName;
        // e.g. $yourControllerClassName = 'AdminMyControllerName'
        // Here $yourControllerClassName is the name of your controller's Class

        $tab->name = array();

        foreach (Language::getLanguages(true) as $lang) {

            $tab->name[$lang['id_lang']] = $yourTabName;
            // e.g. $yourTabName = 'My Tab Name'
            // Here $yourTabName is the name of your Tab
        }
        if ($tabParentControllerName) {
            $tab->id_parent = (int) Tab::getIdFromClassName($tabParentControllerName);
            // e.g. $tabParentControllerName = 'AdminParentAdminControllerName'
            // Here $tabParentControllerName  is the name of the controller under which Admin Controller's tab you want to put your controller's Tab

        } else {

            // If you want to make your controller's Tab as parent Tab in this case send id_parent as 0
            $tab->id_parent = 0;
        }

        // $this->name is the name of your module to which your admin controller belongs.
        // As we generally create it in module's installation So you can get module's name by $this->name in module's main file

        $tab->module = $this->name;
        // e.g. $this->name = 'MyModuleName'

        return !!$tab->add();
        // make an entry of your tab in the _DB_PREFIX_.'tab' table.
    }
    public function uninstallTab($yourControllerClassName){
        $id_tab = (int)Tab::getIdFromClassName($yourControllerClassName);
        if ($id_tab) {
            $tab = new \Tab($id_tab);
            return $tab->delete();
        } else {
            return false;
        }
    }
}
