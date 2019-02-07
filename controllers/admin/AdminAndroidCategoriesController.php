<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 11/01/2019
 * Time: 10:03
 */


class AdminAndroidCategoriesController extends \App\Prestashop\PrestaShopAdminModule
{
    protected $add_button_submit = "addCategory";
    use \TraitDBCategoriesAndroid;


    function getConfigFormValues()
    {
        return array_merge([
            'ANDROIDMANAGER_CATEGORY_COUNT' => Configuration::get('ANDROIDMANAGER_CATEGORY_COUNT'),
            'ANDROIDMANAGER_CATEGORY_LIMIT' => Configuration::get('ANDROIDMANAGER_CATEGORY_LIMIT'),
            'accion' => \Tools::getValue("accion")
        ]);
    }

    function createIndexView()
    {
        $categories_ids = $this->getCategories(true);
        $categories = [];
        foreach ($categories_ids as $key => $cat) {
            $categoria = new \Category($cat['id_category'], $this->context->language->id);
            $categories[$key]['name'] = $categoria->name;
            $categories[$key]['id_android'] = $cat['id_android'];
            $categories[$key]['id_category'] = $cat['id_category'];
            $categories[$key]['position'] = $cat['position'];
        }
        $this->context->smarty->assign(
            array(
                'link' => $this->context->link,
                'link_add' => $this->getAddLink(),
                'categories' => $categories
            )
        );

        return $this->createScriptSortable("categoriesList", "updateCategoriesPosition") .
            $this->createCulqiConfigView() .
            $this->createEditConfigView() .
            $this->module->display($this->module->name, 'listcategory.tpl');
    }

    function createCulqiConfigView()
    {
        $submit = $this->buildHelper();
        $submit->tpl_vars["fields_value"] = [
            "ANDROIDMANAGER_CULQI_PUBLIC_KEY" => Configuration::get('ANDROIDMANAGER_CULQI_PUBLIC_KEY'),
            "ANDROIDMANAGER_CULQI_PRIVATE_KEY" => Configuration::get('ANDROIDMANAGER_CULQI_PRIVATE_KEY'),
            "ANDROIDMANAGER_ALLOW_PAYMENT_CARD" => (bool)Configuration::get('ANDROIDMANAGER_ALLOW_PAYMENT_CARD'),
        ];
        $submit->submit_action = "addCulqiConfigSubmit";
        return
            $submit
                ->generateForm(array([
                    'form' => [
                        'legend' => [
                            'title' => $this->module->l('Culqi Config'),
                            'icon' => 'icon-cogs',
                        ],
                        'input' => [
                            [
                                'label' => $this->module->l('Llave publica Culqi'),
                                'cast' => 'floatval',
                                'type' => 'text',
                                'name' => 'ANDROIDMANAGER_CULQI_PUBLIC_KEY',
                            ],
                            [
                                'label' => $this->module->l('Llave privada Culqi'),
                                'cast' => 'floatval',
                                'type' => 'text',
                                'name' => 'ANDROIDMANAGER_CULQI_PRIVATE_KEY',
                            ],
                            [
                                'label' => $this->module->l('Permitir Compras por Targeta en android'),
                                'desc' => 'Permitir Compras por Targeta',
                                'type' => 'switch',
                                'is_bool' => true,
                                'name' => 'ANDROIDMANAGER_ALLOW_PAYMENT_CARD',
                                'values' => array(
                                    array(
                                        'id' => 'active_on',
                                        'value' => 1,
                                        'label' => $this->getTranslator()->trans('Enabled', array(), 'Admin.Global')
                                    ),
                                    array(
                                        'id' => 'active_off',
                                        'value' => 0,
                                        'label' => $this->getTranslator()->trans('Disabled', array(), 'Admin.Global')
                                    )
                                ),
                            ],
                        ],
                        'submit' => [
                            'title' => $this->module->l('Save'),
                        ],
                    ],
                ]));
    }

    function createEditConfigView()
    {
        $submit = $this->buildHelper();
        $submit->tpl_vars["fields_value"] = [
            "ANDROIDMANAGER_CURRENCY_DEFAULT" => Configuration::get('ANDROIDMANAGER_CURRENCY_DEFAULT'),
            "ANDROIDMANAGER_CARGOS_CARD_PAYMENT" => Configuration::get('ANDROIDMANAGER_CARGOS_CARD_PAYMENT'),
            "ANDROIDMANAGER_INFO_BANCARIA" => base64_decode(Configuration::get('ANDROIDMANAGER_INFO_BANCARIA'))
        ];
        $submit->submit_action = "addCurrencySubmit";
        return
            $submit
                ->generateForm(array([
                    'form' => [
                        'legend' => [
                            'title' => $this->module->l('Default Currency'),
                            'icon' => 'icon-cogs',
                        ],
                        'description' => $this->module->l('Moneda por Defecto en android'),
                        'input' => [
                            [
                                'title' => $this->trans('Default currency', array(), 'Admin.International.Feature'),
                                'cast' => 'intval',
                                'type' => 'select',
                                'identifier' => 'id_currency',
                                'name' => 'ANDROIDMANAGER_CURRENCY_DEFAULT',
                                'options' => [
                                    'query' => Currency::getCurrencies(false, true, true),
                                    'id' => 'id_currency',
                                    'name' => 'name'
                                ]
                            ], [
                                'title' => $this->trans('Monto de recargo por pago con targeta', array(), 'Admin.Android.CartMount'),
                                'label' => $this->module->l('Monto de recargo por pago con targeta'),
                                'desc' => 'Ingrese el monto de recargo por pago con targeta en porcentaje. ejemplo 5 = 5%',
                                'cast' => 'floatval',
                                'type' => 'text',
                                'name' => 'ANDROIDMANAGER_CARGOS_CARD_PAYMENT',
                            ], [
                                'title' => $this->trans('Numeros de cuentas bancarias', array(), 'Admin.Android.BankCuentas'),
                                'label' => $this->module->l('Numeros de cuentas bancarias'),
                                'desc' => 'Numeros de cuentas bancarias',
                                'type' => 'textarea',
                                'name' => 'ANDROIDMANAGER_INFO_BANCARIA',
                            ],
                        ],
                        'submit' => [
                            'title' => $this->module->l('Save'),
                        ],
                    ],
                ]));
    }

    function createEditView()
    {
        $submit = $this->buildHelper();
        $submit->currentIndex = $this->getAddLink();
        $submit->submit_action = "addCategorySubmit";
        return
            $submit
                ->generateForm(array([
                    'form' => [
                        'legend' => [
                            'title' => $this->module->l('Categorías'),
                            'icon' => 'icon-cogs',
                        ],
                        'description' => $this->module->l('Selecciona 3 categorías para mostrar en la aplicación android'),
                        'input' => [
                            $this->inputActionForm(),
                            [
                                "required" => "required",
                                'col' => 6,
                                'type' => 'text',
                                'prefix' => '<i class="icon icon-key"></i>',
                                'label' => $this->module->l('Numero de categorias'),
                                'desc' => 'Cantidad de Categorías Seleccionadas',
                                'name' => 'ANDROIDMANAGER_CATEGORY_COUNT',
                            ],
                            [
                                "required" => "required",
                                'col' => 6,
                                'type' => 'text',
                                'prefix' => '<i class="icon icon-key"></i>',
                                'desc' => 'Limite de productos por categoría',
                                'name' => 'ANDROIDMANAGER_CATEGORY_LIMIT',
                                'label' => $this->module->l('Limite'),
                            ],
                            [
                                'col' => 6,
                                'type' => 'categories',
                                'name' => 'ANDROIDMANAGER_CATEGORY_ALL',
                                'tree' => [
                                    "title" => "Categorías",
                                    "id" => 1,
                                    "selected_categories" => $this->getSelectedCategories(),
                                    "use_search" => true,
                                    "use_checkbox" => true
                                ],
                            ],

                        ],
                        'submit' => [
                            'title' => $this->module->l('Save'),
                        ],
                    ],
                ]));
    }

    public function postProcess()
    {
        if (\Tools::isSubmit("addCurrencySubmit")) {

            $ANDROIDMANAGER_CURRENCY_DEFAULT = (int)Tools::getValue('ANDROIDMANAGER_CURRENCY_DEFAULT');
            $ANDROIDMANAGER_CARGOS_CARD = (Tools::getValue('ANDROIDMANAGER_CARGOS_CARD_PAYMENT') ?: 5);
            $ANDROIDMANAGER_INFO_BANCARIA = Tools::getValue('ANDROIDMANAGER_INFO_BANCARIA');
            $ANDROIDMANAGER_ALLOW_PAYMENT_CARD = Tools::getValue('ANDROIDMANAGER_ALLOW_PAYMENT_CARD');

            Configuration::updateValue("ANDROIDMANAGER_ALLOW_PAYMENT_CARD", $ANDROIDMANAGER_ALLOW_PAYMENT_CARD);
            Configuration::updateValue("ANDROIDMANAGER_CURRENCY_DEFAULT", $ANDROIDMANAGER_CURRENCY_DEFAULT);
            Configuration::updateValue("ANDROIDMANAGER_CARGOS_CARD_PAYMENT", $ANDROIDMANAGER_CARGOS_CARD);
            Configuration::updateValue("ANDROIDMANAGER_INFO_BANCARIA", base64_encode($ANDROIDMANAGER_INFO_BANCARIA ?: ""));
            $this->setSessionSuccess();
        }
        if (\Tools::isSubmit("addCulqiConfigSubmit")) {

            $ANDROIDMANAGER_ALLOW_PAYMENT_CARD = (bool)Tools::getValue('ANDROIDMANAGER_ALLOW_PAYMENT_CARD');
            $ANDROIDMANAGER_CULQI_PRIVATE_KEY = Tools::getValue('ANDROIDMANAGER_CULQI_PRIVATE_KEY');
            $ANDROIDMANAGER_CULQI_PUBLIC_KEY = Tools::getValue('ANDROIDMANAGER_CULQI_PUBLIC_KEY');

            Configuration::updateValue("ANDROIDMANAGER_ALLOW_PAYMENT_CARD", $ANDROIDMANAGER_ALLOW_PAYMENT_CARD);
            Configuration::updateValue("ANDROIDMANAGER_CULQI_PRIVATE_KEY", $ANDROIDMANAGER_CULQI_PRIVATE_KEY);
            Configuration::updateValue("ANDROIDMANAGER_CULQI_PUBLIC_KEY", $ANDROIDMANAGER_CULQI_PUBLIC_KEY);

            $this->setSessionSuccess();
        }
        return parent::postProcess();

    }

    function onEditPostProccess()
    {
    }

    function isSubmit()
    {
        return parent::isSubmit() && \Tools::isSubmit("addCategorySubmit");
    }

    function onAddPostProccess()
    {
        if ($this->isSubmit() && $this->validation()) {
            $res = true;
            //$this->module->clearCache();
            $ANDROIDMANAGER_CATEGORY_COUNT = (int)Tools::getValue('ANDROIDMANAGER_CATEGORY_COUNT');
            $ANDROIDMANAGER_CATEGORY_LIMIT = (int)Tools::getValue('ANDROIDMANAGER_CATEGORY_LIMIT');

            Configuration::updateValue("ANDROIDMANAGER_CATEGORY_COUNT", $ANDROIDMANAGER_CATEGORY_COUNT);

            Configuration::updateValue("ANDROIDMANAGER_CATEGORY_LIMIT", $ANDROIDMANAGER_CATEGORY_LIMIT);

            $values = Tools::getValue("ANDROIDMANAGER_CATEGORY_ALL") ?: [];
            if (count($values) > 0) {
                $selected = $this->getSelectedCategoriesWithId();
                foreach ($selected as $s) {
                    $MODEL = new \AndroidCategory($s['id']);
                    $MODEL->id_category = $s['id_category'];
                    $MODEL->id_shop = 0;
                    $MODEL->delete();
                }
            }
            foreach ($values as $key => $val) {
                if ($key < $ANDROIDMANAGER_CATEGORY_COUNT) {
                    $MODEL = new \AndroidCategory($key);
                    $MODEL->id_yamoshiandroid_categories = $key;
                    $MODEL->active = 1;
                    $MODEL->position = $key;
                    $MODEL->id_category = $val;
                    $MODEL->id_shop = 0;
                    $res &= $MODEL->add();
                }
            }

            if (!$res) {
                $this->errors[] = $this->module->displayError($this->module->getTranslator()->trans('The categories could not be updated.', array(), 'Modules.AndroidSlide.Admin'));
            } else {
                $this->redirectDefaultModule();
            }
        }
    }

    function validation()
    {
        if ($this->isSubmit()) {
            $val_category_ids = Tools::getValue('ANDROIDMANAGER_CATEGORY_ALL');
            $ANDROIDMANAGER_CATEGORY_COUNT = (int)Tools::getValue('ANDROIDMANAGER_CATEGORY_COUNT');
            $ANDROIDMANAGER_CATEGORY_LIMIT = (int)Tools::getValue('ANDROIDMANAGER_CATEGORY_LIMIT');

            if ($ANDROIDMANAGER_CATEGORY_COUNT <= 0) {
                $this->errors[] = $this->module->getTranslator()->trans('Numero de categorias debe ser mayor a cero', array(), 'Modules.AndroidSlide.Admin');
                return false;
            }
            if ($ANDROIDMANAGER_CATEGORY_LIMIT <= 0) {
                $this->errors[] = $this->module->getTranslator()->trans('Limite de categorias debe ser mayor a cero', array(), 'Modules.AndroidSlide.Admin');
                return false;
            }
            if (!is_array($val_category_ids) || count($val_category_ids) <= 0) {
                $this->errors[] = $this->module->getTranslator()->trans('Seleccione almenos 1 categoria', array(), 'Modules.AndroidSlide.Admin');
                return false;
            }
        }
        return true;
    }
}
