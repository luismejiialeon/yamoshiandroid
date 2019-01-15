<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 11/01/2019
 * Time: 10:03
 */


class AdminAndroidSldierController extends \App\Prestashop\PrestaShopAdminModule
{
    use TraitDBSliderAndroid;

    const  setting_submit_button = "submitSlider";
    const  edit_submit_button = "submitSlide";
    const  activar_submit_button = "changeStatusSlider";
    const  delete_submit_button = "deleteSlider";

    function validation()
    {
        if (\Tools::isSubmit(self::setting_submit_button)) {
            if (!\Validate::isInt(Tools::getValue('ANDROIDSLIDER_SPEED'))) {
                $this->errors[] = $this->module->getTranslator()->trans('Invalid values', array(), 'Modules.AndroidSlide.Admin');
                return false;
            }
        } elseif (\Tools::isSubmit(self::activar_submit_button) || \Tools::isSubmit(self::delete_submit_button)) {
            if (!Validate::isInt(Tools::getValue('id_slide')) && !$this->slideExists(Tools::getValue('id_slide'))) {
                $this->errors[] = $this->getTranslator()->trans('Invalid slide ID', array(), 'Modules.AndroidSlide.Admin');
            }
        } elseif (\Tools::isSubmit(self::edit_submit_button)) {
            $serrors=[];
            /* Checks state (active) */
            if (!Validate::isInt(Tools::getValue('active_slide')) || (Tools::getValue('active_slide') != 0 && Tools::getValue('active_slide') != 1)) {
                $serrors[] = $this->getTranslator()->trans('Invalid slide state.', array(), 'Modules.AndroidSlide.Admin');
            }
            /* Checks position */
            if (!Validate::isInt(Tools::getValue('position')) || (Tools::getValue('position') < 0)) {
                $serrors[] = $this->getTranslator()->trans('Invalid slide position.', array(), 'Modules.AndroidSlide.Admin');
            }
            /* If edit : checks id_slide */
            if (Tools::isSubmit('id_slide')) {
                if (!Validate::isInt(Tools::getValue('id_slide')) && !$this->slideExists(Tools::getValue('id_slide'))) {
                    $serrors[] = $this->getTranslator()->trans('Invalid slide ID', array(), 'Modules.AndroidSlide.Admin');
                }
            }
            /* Checks title/url/legend/description/image */
            $languages = Language::getLanguages(false);

            foreach ($languages as $language) {
                if (Tools::strlen(Tools::getValue('title_' . $language['id_lang'])) > 255) {
                    $serrors[] = $this->getTranslator()->trans('The title is too long.', array(), 'Modules.AndroidSlide.Admin');
                }
                if (Tools::strlen(Tools::getValue('legend_' . $language['id_lang'])) > 255) {
                    $serrors[] = $this->getTranslator()->trans('The caption is too long.', array(), 'Modules.AndroidSlide.Admin');
                }
                if (Tools::strlen(Tools::getValue('url_' . $language['id_lang'])) > 255) {
                    $serrors[] = $this->getTranslator()->trans('The URL is too long.', array(), 'Modules.AndroidSlide.Admin');
                }
                if (Tools::strlen(Tools::getValue('description_' . $language['id_lang'])) > 4000) {
                    $serrors[] = $this->getTranslator()->trans('The description is too long.', array(), 'Modules.AndroidSlide.Admin');
                }
                if (Tools::strlen(Tools::getValue('url_' . $language['id_lang'])) > 0 && !Validate::isUrl(Tools::getValue('url_' . $language['id_lang']))) {
                    $serrors[] = $this->getTranslator()->trans('The URL format is not correct.', array(), 'Modules.AndroidSlide.Admin');
                }
                if (Tools::getValue('image_' . $language['id_lang']) != null && !Validate::isFileName(Tools::getValue('image_' . $language['id_lang']))) {
                    $serrors[] = $this->getTranslator()->trans('Invalid filename.', array(), 'Modules.AndroidSlide.Admin');
                }
                if (Tools::getValue('image_old_' . $language['id_lang']) != null && !Validate::isFileName(Tools::getValue('image_old_' . $language['id_lang']))) {
                    $serrors[] = $this->getTranslator()->trans('Invalid filename.', array(), 'Modules.AndroidSlide.Admin');
                }

            }

            /* Checks title/url/legend/description for default lang */
            $id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT');
            if (Tools::strlen(Tools::getValue('url_' . $id_lang_default)) == 0) {
                $serrors[] = $this->getTranslator()->trans('The URL is not set.', array(), 'Modules.AndroidSlide.Admin');
            }
            if (!Tools::isSubmit('has_picture') && (!isset($_FILES['image_' . $id_lang_default]) || empty($_FILES['image_' . $id_lang_default]['tmp_name']))) {
                $serrors[] = $this->getTranslator()->trans('The image is not set.', array(), 'Modules.AndroidSlide.Admin');
            }
            if (Tools::getValue('image_old_' . $id_lang_default) && !Validate::isFileName(Tools::getValue('image_old_' . $id_lang_default))) {
                $serrors[] = $this->getTranslator()->trans('The image is not set.', array(), 'Modules.AndroidSlide.Admin');
            }

            foreach ($serrors as $err) {
                $this->errors[] = $err;
            }
            if(count($serrors)>0){
                return false;
            }
        }
        return true;
    }

    public function displayStatus($id_slide, $active)
    {
        $title = ((int)$active == 0 ? $this->getTranslator()
            ->trans('Disabled', array(), 'Admin.Global') :
            $this->getTranslator()->trans('Enabled', array(), 'Admin.Global'));
        $icon = ((int)$active == 0 ? 'icon-remove' : 'icon-check');
        $class = ((int)$active == 0 ? 'btn-danger' : 'btn-success');
        $html = '<a class="btn ' . $class . '" href="' .
            $this->getControllerLink("changeStatusSlider", [
                "id_slide" => (int)$id_slide
            ]) . '" title="' . $title . '"><i class="' . $icon . '"></i> ' . $title . '</a>';

        return $html;
    }

    public function renderSliderList()
    {
        $slides = $this->getSlides();
        foreach ($slides as $key => $slide) {
            $slides[$key]['status'] = $this->displayStatus($slide['id_slide'], $slide['active']);
            $associated_shop_ids = AndroidSlider::getAssociatedIdsShop((int)$slide['id_slide']);
            if ($associated_shop_ids && count($associated_shop_ids) > 1) {
                $slides[$key]['is_shared'] = true;
            } else {
                $slides[$key]['is_shared'] = false;
            }
        }

        $this->context->smarty->assign(
            array(
                'link' => $this->context->link,
                'controllerSelft' => $this,
                'slides' => $slides,
                'image_baseurl' => $this->module->getPathUri() . 'images/'
            )
        );

        return $this->createScriptSortable("slides","updateSlidesPosition").$this->module->display($this->module->name, 'list.tpl');
    }

    function createIndexView()
    {
        return $this->buildHelper(self::setting_submit_button)->generateForm(array(array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->module->getTranslator()->trans('Settings', array(), 'Admin.Global'),
                        'icon' => 'icon-cogs'
                    ),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->getTranslator()->trans('Speed', array(), 'Modules.AndroidSlide.Admin'),
                            'name' => 'ANDROIDSLIDER_SPEED',
                            'suffix' => 'milliseconds',
                            'class' => 'fixed-width-sm',
                            'desc' => $this->getTranslator()->trans('The duration of the transition between two slides.', array(), 'Modules.AndroidSlide.Admin')
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->getTranslator()->trans('Pause on hover', array(), 'Modules.AndroidSlide.Admin'),
                            'name' => 'ANDROIDSLIDER_PAUSE_ON_HOVER',
                            'desc' => $this->getTranslator()->trans('Stop sliding when the mouse cursor is over the slideshow.', array(), 'Modules.AndroidSlide.Admin'),
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
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->getTranslator()->trans('Loop forever', array(), 'Modules.AndroidSlide.Admin'),
                            'name' => 'ANDROIDSLIDER_WRAP',
                            'desc' => $this->getTranslator()->trans('Loop or stop after the last slide.', array(), 'Modules.AndroidSlide.Admin'),
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
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->getTranslator()->trans('Save', array(), 'Admin.Actions'),
                    )
                ),
            )))
            . $this->renderSliderList();
    }


    function createEditView()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->getTranslator()->trans('Slide information', array(), 'Modules.AndroidSlide.Admin'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'file_lang',
                        'label' => $this->getTranslator()->trans('Image', array(), 'Admin.Global'),
                        'name' => 'image',
                        'required' => true,
                        'lang' => true,
                        'desc' => $this->getTranslator()->trans('Maximum image size: %s.', array(ini_get('upload_max_filesize')), 'Admin.Global')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->getTranslator()->trans('Title', array(), 'Admin.Global'),
                        'name' => 'title',
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->getTranslator()->trans('Target URL', array(), 'Modules.AndroidSlide.Admin'),
                        'name' => 'url',
                        'required' => true,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->getTranslator()->trans('Caption', array(), 'Modules.AndroidSlide.Admin'),
                        'name' => 'legend',
                        'lang' => true,
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->getTranslator()->trans('Description', array(), 'Admin.Global'),
                        'name' => 'description',
                        'autoload_rte' => true,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('Enabled', array(), 'Admin.Global'),
                        'name' => 'active_slide',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->getTranslator()->trans('Yes', array(), 'Admin.Global')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->getTranslator()->trans('No', array(), 'Admin.Global')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->getTranslator()->trans('Save', array(), 'Admin.Actions'),
                )
            ),
        );

        if (Tools::isSubmit('id_slide') && $this->slideExists((int)Tools::getValue('id_slide'))) {
            $slide = new AndroidSlider((int)Tools::getValue('id_slide'));
            $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'id_slide');
            $fields_form['form']['images'] = $slide->image;

            $has_picture = true;

            foreach (Language::getLanguages(false) as $lang) {
                if (!isset($slide->image[$lang['id_lang']])) {
                    $has_picture &= false;
                }
            }

            if ($has_picture) {
                $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'has_picture');
            }
        }

        $helper = $this->buildHelper(self::edit_submit_button);
        $helper->currentIndex = $this->getEditLink([
            "id_slide" => Tools::getValue('id_slide')
        ]);
        $language = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

        $helper->tpl_vars = array(
            'base_url' => $this->context->shop->getBaseURL(),
            'language' => array(
                'id_lang' => $language->id,
                'iso_code' => $language->iso_code
            ),
            'fields_value' => $this->getAddFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'image_baseurl' => $this->module->getPathUri() . 'images/'
        );

        $languages = Language::getLanguages(false);

        if (count($languages) > 1) {
            return $this->getMultiLanguageInfoMsg() . $helper->generateForm(array($fields_form));
        } else {
            return $helper->generateForm(array($fields_form));
        }
    }


    public function postProcess()
    {
        if (\Tools::isSubmit(self::setting_submit_button)) {

            if ($this->validation()) {
                $shop_context = \Shop::getContext();
                $shop_groups_list = array();
                $shops = \Shop::getContextListShopID();

                foreach ($shops as $shop_id) {
                    $shop_group_id = (int)\Shop::getGroupFromShop($shop_id, true);

                    if (!in_array($shop_group_id, $shop_groups_list)) {
                        $shop_groups_list[] = $shop_group_id;
                    }

                    $res = Configuration::updateValue('ANDROIDSLIDER_SPEED', (int)Tools::getValue('ANDROIDSLIDER_SPEED'), false, $shop_group_id, $shop_id);
                    $res &= Configuration::updateValue('ANDROIDSLIDER_PAUSE_ON_HOVER', (int)Tools::getValue('ANDROIDSLIDER_PAUSE_ON_HOVER'), false, $shop_group_id, $shop_id);
                    $res &= Configuration::updateValue('ANDROIDSLIDER_WRAP', (int)Tools::getValue('ANDROIDSLIDER_WRAP'), false, $shop_group_id, $shop_id);
                }

                /* Update global shop context if needed*/
                switch ($shop_context) {
                    case \Shop::CONTEXT_ALL:
                        $res &= Configuration::updateValue('ANDROIDSLIDER_SPEED', (int)Tools::getValue('ANDROIDSLIDER_SPEED'));
                        $res &= Configuration::updateValue('ANDROIDSLIDER_PAUSE_ON_HOVER', (int)Tools::getValue('ANDROIDSLIDER_PAUSE_ON_HOVER'));
                        $res &= Configuration::updateValue('ANDROIDSLIDER_WRAP', (int)Tools::getValue('ANDROIDSLIDER_WRAP'));
                        if (count($shop_groups_list)) {
                            foreach ($shop_groups_list as $shop_group_id) {
                                $res &= Configuration::updateValue('ANDROIDSLIDER_SPEED', (int)Tools::getValue('ANDROIDSLIDER_SPEED'), false, $shop_group_id);
                                $res &= Configuration::updateValue('ANDROIDSLIDER_PAUSE_ON_HOVER', (int)Tools::getValue('ANDROIDSLIDER_PAUSE_ON_HOVER'), false, $shop_group_id);
                                $res &= Configuration::updateValue('ANDROIDSLIDER_WRAP', (int)Tools::getValue('ANDROIDSLIDER_WRAP'), false, $shop_group_id);
                            }
                        }
                        break;
                    case \Shop::CONTEXT_GROUP:
                        if (count($shop_groups_list)) {
                            foreach ($shop_groups_list as $shop_group_id) {
                                $res &= Configuration::updateValue('ANDROIDSLIDER_SPEED', (int)Tools::getValue('ANDROIDSLIDER_SPEED'), false, $shop_group_id);
                                $res &= Configuration::updateValue('ANDROIDSLIDER_PAUSE_ON_HOVER', (int)Tools::getValue('ANDROIDSLIDER_PAUSE_ON_HOVER'), false, $shop_group_id);
                                $res &= Configuration::updateValue('ANDROIDSLIDER_WRAP', (int)Tools::getValue('ANDROIDSLIDER_WRAP'), false, $shop_group_id);
                            }
                        }
                        break;
                }

                //$this->module->clearCache();

                if (!$res) {
                    $this->errors[] = $this->module->displayError($this->module->getTranslator()->trans('The configuration could not be updated.', array(), 'Modules.AndroidSlide.Admin'));
                } else {
                    $this->setSessionSuccess();
                    return true;
                }
            }

            return false;
        } else if (\Tools::isSubmit(self::activar_submit_button)) {
            if ($this->validation()) {
                $slide = new AndroidSlider((int)Tools::getValue('id_slide'));
                if ($slide->active == 0) {
                    $slide->active = 1;
                } else {
                    $slide->active = 0;
                }
                $res = $slide->update();
                if (!$res) {
                    $this->errors[] = $this->displayError('Could not change status.');
                } else {
                    $this->setSessionSuccess();
                    Tools::redirectAdmin($this->getControllerLink());
                }
                //$this->clearCache();
                //$this->_html .= ($res ? $this->displayConfirmation($this->getTranslator()->trans('Configuration updated', array(), 'Admin.Notifications.Success')) : $this->displayError($this->getTranslator()->trans('The configuration could not be updated.', array(), 'Modules.Imageslider.Admin')));
            }
            return false;
        } else if (\Tools::isSubmit(self::delete_submit_button)) {
            if ($this->validation()) {
                $slide = new AndroidSlider((int)Tools::getValue('id_slide'));
                $res = $slide->delete();
                //$this->clearCache();
                if (!$res) {
                    $this->errors[] = $this->displayError('Could not delete.');
                } else {
                    $this->setSessionSuccess();
                    Tools::redirectAdmin($this->getControllerLink());
                }
            }
            return false;
        } else if($this->isSubmit() && \Tools::isSubmit(self::edit_submit_button)) {
            $errors=[];
            /* Sets ID if needed */
            if (Tools::getValue('id_slide')) {
                $slide = new AndroidSlider((int)Tools::getValue('id_slide'));
                if (!Validate::isLoadedObject($slide)) {
                    $errors[]= $this->displayError($this->getTranslator()->trans('Invalid slide ID', array(), 'Modules.AndroidSlide.Admin'));
                    return false;
                }
            } else {
                $slide = new AndroidSlider();
            }
            /* Sets position */
            $slide->position = (int)Tools::getValue('position');
            /* Sets active */
            $slide->active = (int)Tools::getValue('active_slide');

            /* Sets each langue fields */
            $languages = Language::getLanguages(false);

            foreach ($languages as $language) {
                $slide->title[$language['id_lang']] = Tools::getValue('title_' . $language['id_lang']);
                $slide->url[$language['id_lang']] = Tools::getValue('url_' . $language['id_lang']);
                $slide->legend[$language['id_lang']] = Tools::getValue('legend_' . $language['id_lang']);
                $slide->description[$language['id_lang']] = Tools::getValue('description_' . $language['id_lang']);

                /* Uploads image and sets slide */
                $type = Tools::strtolower(Tools::substr(strrchr($_FILES['image_' . $language['id_lang']]['name'], '.'), 1));
                $imagesize = @getimagesize($_FILES['image_' . $language['id_lang']]['tmp_name']);
                if (isset($_FILES['image_' . $language['id_lang']]) &&
                    isset($_FILES['image_' . $language['id_lang']]['tmp_name']) &&
                    !empty($_FILES['image_' . $language['id_lang']]['tmp_name']) &&
                    !empty($imagesize) &&
                    in_array(
                        Tools::strtolower(Tools::substr(strrchr($imagesize['mime'], '/'), 1)), array(
                            'jpg',
                            'gif',
                            'jpeg',
                            'png'
                        )
                    ) &&
                    in_array($type, array('jpg', 'gif', 'jpeg', 'png'))
                ) {
                    $temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');
                    $salt = sha1(microtime());
                    if ($error = ImageManager::validateUpload($_FILES['image_' . $language['id_lang']])) {
                        $errors[] = $error;
                    } elseif (!$temp_name || !move_uploaded_file($_FILES['image_' . $language['id_lang']]['tmp_name'], $temp_name)) {
                        return false;
                    } elseif (!ImageManager::resize($temp_name, dirname(dirname(dirname(__FILE__))) . '/images/' . $salt . '_' . $_FILES['image_' . $language['id_lang']]['name'], null, null, $type)) {
                        $errors[] = $this->displayError($this->getTranslator()->trans('An error occurred during the image upload process.', array(), 'Admin.Notifications.Error'));
                    }
                    if (isset($temp_name)) {
                        @unlink($temp_name);
                    }

                    $slide->image[$language['id_lang']] = $salt . '_' . $_FILES['image_' . $language['id_lang']]['name'];
                } elseif (Tools::getValue('image_old_' . $language['id_lang']) != '') {
                    $slide->image[$language['id_lang']] = Tools::getValue('image_old_' . $language['id_lang']);
                }
            }

            /* Processes if no errors  */
            if (!$errors) {
                /* Adds */
                if (!Tools::getValue('id_slide')) {
                    if (!$slide->add()) {
                        $errors[] = $this->displayError($this->getTranslator()->trans('The slide could not be added.', array(), 'Modules.AndroidSlide.Admin'));
                    }else{
                        $this->setSessionSuccess();
                        $this->redirectDefaultModule();
                    }
                } elseif (!$slide->update()) {
                    $errors[] = $this->displayError($this->getTranslator()->trans('The slide could not be updated.', array(), 'Modules.AndroidSlide.Admin'));
                }else{
                    $this->setSessionSuccess();
                    $this->redirectDefaultModule();
                }
               // $this->clearCache();
            }
        }else{
            return parent::postProcess();
        }
    }

    function onAddPostProccess()
    {

    }

    function onEditPostProccess()
    {

    }

    function getAddFieldsValues()
    {
        $fields = array();

        if (Tools::isSubmit('id_slide') && $this->slideExists((int)Tools::getValue('id_slide'))) {
            $slide = new AndroidSlider((int)Tools::getValue('id_slide'));
            $fields['id_slide'] = (int)Tools::getValue('id_slide', $slide->id);
        } else {
            $slide = new AndroidSlider();
        }

        $fields['active_slide'] = Tools::getValue('active_slide', $slide->active);
        $fields['has_picture'] = true;

        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            $fields['image'][$lang['id_lang']] = Tools::getValue('image_' . (int)$lang['id_lang']);
            $fields['title'][$lang['id_lang']] = Tools::getValue('title_' . (int)$lang['id_lang'], $slide->title[$lang['id_lang']]);
            $fields['url'][$lang['id_lang']] = Tools::getValue('url_' . (int)$lang['id_lang'], $slide->url[$lang['id_lang']]);
            $fields['legend'][$lang['id_lang']] = Tools::getValue('legend_' . (int)$lang['id_lang'], $slide->legend[$lang['id_lang']]);
            $fields['description'][$lang['id_lang']] = Tools::getValue('description_' . (int)$lang['id_lang'], $slide->description[$lang['id_lang']]);
        }

        return $fields;
    }

    function getConfigFormValues()
    {
        $id_shop_group = \Shop::getContextShopGroupID();
        $id_shop = \Shop::getContextShopID();

        return array(
            'ANDROIDSLIDER_SPEED' => Tools::getValue('ANDROIDSLIDER_SPEED', Configuration::get('ANDROIDSLIDER_SPEED', null, $id_shop_group, $id_shop)),
            'ANDROIDSLIDER_PAUSE_ON_HOVER' => Tools::getValue('ANDROIDSLIDER_PAUSE_ON_HOVER', Configuration::get('ANDROIDSLIDER_PAUSE_ON_HOVER', null, $id_shop_group, $id_shop)),
            'ANDROIDSLIDER_WRAP' => Tools::getValue('ANDROIDSLIDER_WRAP', Configuration::get('ANDROIDSLIDER_WRAP', null, $id_shop_group, $id_shop)),
        );
    }
}
