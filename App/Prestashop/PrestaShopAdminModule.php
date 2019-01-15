<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 11/01/2019
 * Time: 12:34
 */

namespace App\Prestashop;


use Shop;


abstract class PrestaShopAdminModule extends \ModuleAdminController
{
    private $name;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        parent::__construct();
    }

    public function init()
    {
        if (\Tools::isSubmit($this->getEditKeyName()) || \Tools::getValue("accion") == $this->getEditKeyName()) {
            $this->display = 'edit';
        } elseif (\Tools::isSubmit($this->getAddKeyName()) || \Tools::getValue("accion") == $this->getAddKeyName()) {
            $this->display = 'add';
        }

        parent::init();
    }

    public function getEditKeyName()
    {
        return $this->controller_name . "Edit";
    }

    public function getAddKeyName()
    {
        return $this->controller_name . "Add";
    }

    public function getAddLink()
    {
        return $this->getControllerLink($this->getAddKeyName());
    }

    public function getEditLink($params = array())
    {
        return $this->getControllerLink($this->getEditKeyName(), $params);
    }

    public function postProcess()
    {
        if ($this->display == 'edit') {
            $this->onEditPostProccess();
        } else {
            $this->onAddPostProccess();
        }
        return parent::postProcess();
    }

    public function renderView()
    {
        $html = "";
        if ($this->isSessionSuccess()) {
            $html .= '
            <div class="alert alert-success">
                        Configuración ha sido guardada
                    </div>
            ';
            $this->removeSession();
        }
        $html .= $this->createIndexView() ?: parent::renderView();
        return $html;
    }

    public function renderForm()
    {
        return $this->createEditView() ?: parent::renderForm();
    }

    protected function buildHelper($buttonSubmit = null)
    {
        $helper = new \HelperForm();

        $helper->module = $this->module;
        //$helper->override_folder = 'linkwidget/';
        $helper->identifier = $this->className;
        $helper->token = \Tools::getAdminTokenLite($this->controller_name);
        $helper->languages = $this->_languages;
        $helper->currentIndex = $this->getControllerLink();
        $helper->default_form_language = $this->default_form_language;
        $helper->allow_employee_form_lang = $this->allow_employee_form_lang;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = $this->initToolbar();
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues() ?: [],
            'languages' => $this->getLanguages(),
            'id_language' => $this->context->language->id
        );
        if ($buttonSubmit != null) {
            $helper->submit_action = $buttonSubmit;
        }
        return $helper;
    }

    public function getControllerLink($action = null, $params = array())
    {
        $queries = [];
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                if (is_string($key)) {
                    $queries[] = "$key=$value";
                } else if (is_string($value)) {
                    $queries[] = "$value";
                }
            }
        }
        return $this->context->link
                ->getAdminLink($this->controller_name)
            . ((!!$action) ? "&amp;accion=$action&$action=1" : '') . (count($queries) > 0 ? "&" . implode("&", $queries) : '');
    }

    public function inputActionForm()
    {
        return
            [
                'type' => 'hidden',
                'name' => "accion"
            ];
    }

    public function displayError($error)
    {
        $output = '
        <div class="bootstrap">
        <div class="module_error alert alert-danger" >
            <button type="button" class="close" data-dismiss="alert">&times;</button>';

        if (is_array($error)) {
            $output .= '<ul>';
            foreach ($error as $msg) {
                $output .= '<li>' . $msg . '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= $error;
        }

        // Close div openned previously
        $output .= '</div></div>';

        // $this->error = true;
        return $output;
    }

    public function createScriptSortable($id_content, $action)
    {
        $this->context->controller->addJqueryUI('ui.sortable');
        /* Style & js for fieldset 'slides configuration' */
        $html = '<script type="text/javascript">
            $(function() {
                var $mySlides = $("#' . $id_content . '");
                $mySlides.sortable({
                    opacity: 0.6,
                    cursor: "move",
                    update: function() {
                        var order = $(this).sortable("serialize") + "&action=' . $action . '";
                        $.post("' . $this->context->shop->physical_uri . $this->context->shop->virtual_uri . 'modules/' . $this->module->name . '/ajax_' . $this->module->name . '.php?secure_key=' . $this->module->secure_key . '", order);
                        }
                    });
                $mySlides.hover(function() {
                    $(this).css("cursor","move");
                    },
                    function() {
                    $(this).css("cursor","auto");
                });
            });
        </script>';

        return $html;
    }

    protected function isSubmit()
    {
        return (
            \Tools::isSubmit($this->getEditKeyName()) ||
            \Tools::getValue("accion") == $this->getEditKeyName() ||
            \Tools::isSubmit($this->getAddKeyName()) ||
            \Tools::getValue("accion") == $this->getAddKeyName()
        );
    }

    public function redirectDefaultModule()
    {
        $this->setSessionSuccess();
        \Tools::redirectAdmin($this->getControllerLink(null));
        exit;
    }

    public function setSessionSuccess()
    {
        $context = \Context::getContext();
        $context->cookie->__set("sysml_success_save", "true");
        $_SESSION["sysml_success_save"] = "true";
    }

    public function isSessionSuccess()
    {
        $context = \Context::getContext();

        return $context->cookie->__isset("sysml_success_save") || isset($_SESSION["sysml_success_save"]);
    }

    public function removeSession()
    {
        $context = \Context::getContext();
        $context->cookie->__unset("sysml_success_save");
        unset($_SESSION["sysml_success_save"]);
    }

    protected function getTranslator()
    {
        return $this->module->getTranslator();
    }

    protected function updateUrl($link)
    {
        if (substr($link, 0, 7) !== "http://" && substr($link, 0, 8) !== "https://") {
            $link = "http://" . $link;
        }

        return $link;
    }

    protected function getMultiLanguageInfoMsg()
    {
        return '<p class="alert alert-warning">' .
            $this->getTranslator()->trans('Since multiple languages are activated on your shop, please mind to upload your image for each one of them', array(), 'Modules.AndroidSlide.Admin') .
            '</p>';
    }

    protected function getWarningMultishopHtml()
    {
        if (Shop::getContext() == Shop::CONTEXT_GROUP || Shop::getContext() == Shop::CONTEXT_ALL) {
            return '<p class="alert alert-warning">' .
                $this->getTranslator()->trans('You cannot manage slides items from a "All Shops" or a "Group Shop" context, select directly the shop you want to edit', array(), 'Modules.AndroidSlide.Admin') .
                '</p>';
        } else {
            return '';
        }
    }

    protected function getShopContextError($shop_contextualized_name, $mode)
    {
        if (is_array($shop_contextualized_name)) {
            $shop_contextualized_name = implode('<br/>', $shop_contextualized_name);
        }

        if ($mode == 'edit') {
            return '<p class="alert alert-danger">' .
                $this->trans('You can only edit this slide from the shop(s) context: %s', array($shop_contextualized_name), 'Modules.AndroidSlide.Admin') .
                '</p>';
        } else {
            return '<p class="alert alert-danger">' .
                $this->trans('You cannot add slides from a "All Shops" or a "Group Shop" context', array(), 'Modules.AndroidSlide.Admin') .
                '</p>';
        }
    }

    protected function getShopAssociationError($id_slide)
    {
        return '<p class="alert alert-danger">' .
            $this->trans('Unable to get slide shop association information (id_slide: %d)', array((int)$id_slide), 'Modules.AndroidSlide.Admin') .
            '</p>';
    }


    protected function getCurrentShopInfoMsg()
    {
        $shop_info = null;

        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $shop_info = $this->trans('The modifications will be applied to shop: %s', array($this->context->shop->name), 'Modules.AndroidSlide.Admin');
            } else if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                $shop_info = $this->trans('The modifications will be applied to this group: %s', array(Shop::getContextShopGroup()->name), 'Modules.AndroidSlide.Admin');
            } else {
                $shop_info = $this->trans('The modifications will be applied to all shops and shop groups', array(), 'Modules.AndroidSlide.Admin');
            }

            return '<div class="alert alert-info">' .
                $shop_info .
                '</div>';
        } else {
            return '';
        }
    }

    protected function getSharedSlideWarning()
    {
        return '<p class="alert alert-warning">' .
            $this->trans('This slide is shared with other shops! All shops associated to this slide will apply modifications made here', array(), 'Modules.AndroidSlide.Admin') .
            '</p>';
    }

    abstract function validation();

    abstract function createIndexView();

    abstract function createEditView();

    abstract function onAddPostProccess();

    abstract function onEditPostProccess();

    abstract function getConfigFormValues();

}
