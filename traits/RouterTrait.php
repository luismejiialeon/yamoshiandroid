<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 08/01/2019
 * Time: 7:12
 */

trait RouterTrait
{
    public function hookModuleRoutes()
    {
        return [
            "module-{$this->name}-display" => [
                'controller' => 'display',
                'rule' => 'apiandroid{/:route}{/:path}',
                'keywords' => [
                    'route' => ['regexp' => '[a-zA-Z]+', 'param' => 'route'],
                    'path' => ['regexp' => '(.*)', 'param' => 'path']
                ],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name
                ],
            ],
            /*
            "module-{$this->name}-display" => [
                'controller' => 'display',
                'rule' => 'apiandroid',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name
                ],
            ],
            "module-{$this->name}-categories" => [
                'controller' => 'categories',
                'rule' => 'apiandroid/categories',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name
                ],
            ],
            "module-{$this->name}-imageslider" => [
                'controller' => 'imageslider',
                'rule' => 'apiandroid/imageslider',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name
                ],
            ],*/
        ];
    }
}
