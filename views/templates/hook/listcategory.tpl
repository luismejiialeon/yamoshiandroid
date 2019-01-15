{*
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
*}
<div class="panel">
    <h3>
        <i class="icon-list-ul"></i>
        {l s='Categorias Seleccionadas [Mover de posición]' d='Modules.AndroidSlide.Admin'}
    </h3>
    <div id="slidesContent">
        <div id="categoriesList">
            {foreach from=$categories item=slide}
                <div id="categories_{$slide.id_android}" class="panel">
                    <div class="row">
                        <div class="col-lg-1">
                            <span><i class="icon-arrows "></i></span>
                        </div>
                        <div class="col-md-8">
                            <h4 class="pull-left">
                                #{$slide.id_android} - {$slide.name}
                            </h4>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
    <div class="panel-footer">
        <a href="{$link_add}"
           class="btn btn-default pull-right">
            <i class="process-icon-new"></i>
            Add Categoria
        </a>
    </div>
</div>
