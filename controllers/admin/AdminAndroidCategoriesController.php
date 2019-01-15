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
            $this->module->display($this->module->name, 'listcategory.tpl');
    }

    function createEditView()
    {
        $submit = $this->buildHelper();
        $submit->currentIndex =$this->getAddLink();
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
                                'desc' => 'Cantidad de Categorías Seleccionadas',
                                'name' => 'ANDROIDMANAGER_CATEGORY_COUNT',
                                'label' => $this->module->l('Numero de categorias'),
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
