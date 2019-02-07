<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 6/02/2019
 * Time: 04:56
 */

namespace AndroidHttp;
use App\Android\AndroidRequest;

class ProductsANController extends \ANController
{

    public function index(AndroidRequest $request,$paths)
    {
        return 1;//\Product::getNewProducts(1);
    }

}