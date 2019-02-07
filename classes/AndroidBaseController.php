<?php
/**
 * @author Zoltan Szanto <mrbig00@gmail.com>
 */

use App\Android\AndroidResponse;


/**
 * A base controller which is meant to be extended to serve the right pages for the react generated urls
 *
 * Class ReactController
 */
abstract class AndroidBaseController extends ModuleFrontController
{
    protected $wsKey;

    protected $method_http;
    protected $route;
    protected $exclude_maintenance = array();
    /**
     * @var \App\Support\Collection
     */
    protected $registersrouter;

    public function __construct()
    {
        parent::__construct();

        if (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            list($name, $password) = explode(':', base64_decode($matches[1]));
            $_SERVER['PHP_AUTH_USER'] = strip_tags($name);
        }

//set http auth headers for apache+php-cgi work around if variable gets renamed by apache
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
            list($name, $password) = explode(':', base64_decode($matches[1]));
            $_SERVER['PHP_AUTH_USER'] = strip_tags($name);
        }

// Use for image management (using the POST method of the browser to simulate the PUT method)
        $this->method_http = isset($_REQUEST['ps_method']) ? $_REQUEST['ps_method'] : $_SERVER['REQUEST_METHOD'];

        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $this->wsKey = $_SERVER['PHP_AUTH_USER'];
        } elseif (isset($_GET['ws_key'])) {
            $this->wsKey = $_GET['ws_key'];
        } else {
            $this->wsKey = null;
        }
        $this->route = self:: buildRoute();
        $this->registersrouter = collect(routes_registrados());
        $this->__verifyAuthentication();

    }

    private static function buildRoute()
    {
        $request = request();
        return (object)[
            "module" => $request->get("module", "yamoshiandroid"),
            "fc" => $request->get("fc", "module"),
            "controller" => $request->get("controller", "display"),
            "subcontroller" => $request->get("route", "index"),
            "path" => $request->get("path", ""),
            "paths" => $request->get("path") ? explode("/", $request->get("path", "")) : [],
        ];
    }

    protected function displayRestrictedCountryPage()
    {
        self::__forbidden()->printContent();
    }

    public function displayMaintenancePage()
    {
        if (!in_array($this->route->subcontroller, $this->exclude_maintenance))
            if ($this->maintenance == true || !(int)Configuration::get('PS_SHOP_ENABLE')) {
                if (!in_array(Tools::getRemoteAddr(), explode(',', Configuration::get('PS_MAINTENANCE_IP')))) {
                    $this->maintenance = true;

                    $std = new stdClass();
                    $std->status = 503;
                    $std->type = 'IN_MAINTENANCE';
                    $std->message = Configuration::get('PS_MAINTENANCE_TEXT', (int)$this->context->language->id);
                    $std->PS_SHOP_ENABLE = !Tools::boolVal(Configuration::get('PS_SHOP_ENABLE') ?: false);
                    $std->PS_MAINTENANCE_IP = Configuration::get('PS_MAINTENANCE_IP');
                    $std->PS_MAINTENANCE_TEXT = $std->message;
                    $response = response($std, 503, [
                        'Retry-After: 3600'
                    ]);
                    $response->printContent();
                }
            }
    }

    /**
     * @return \App\Android\AndroidResponse
     */
    public static function __forbidden()
    {
        return response(array(
            'status' => 403,
            'type' => 'FORBIDDEN',
            'message' => 'Acceso Prohibido'
        ), 403);
    }

    public static function __unauthorize()
    {
        return response(array(
            'status' => 401,
            'type' => 'UNAUTHORIZED',
            'message' => 'Acceso no autorizado'
        ), 401);
    }

    /**
     * @return AndroidResponse
     */
    public static function __not_found()
    {
        return response(array(
            'status' => 404,
            'type' => 'NOT_FOUND',
            'message' => 'La pagina no existe'
        ), 404);
    }

    /**
     * @param $detail
     * @return AndroidResponse
     */
    public static function __client_error($detail)
    {
        return response(array(
            'status' => 400,
            'type' => 'CLIENT_ERROR',
            'message' => $detail,
        ), 400);
    }

    /**
     * @param $payload
     * @return AndroidResponse
     */
    public static function __success($payload)
    {
        return response($payload, 200);
    }

    /**
     * @param $elements
     * @return AndroidResponse
     */
    public static function __collection($elements)
    {
        return self::__success($elements);
    }

    public function postProcess()
    {
        $response = $this->__route();
        $response->printContent();
    }

    /**
     * @param string $path
     * @param array $queryArguments
     * @param null|array $payload
     *
     * @return AndroidResponse
     */
    public function defaultindexRoute()
    {
        return response("Empty");
    }

    /**
     * @param $route
     * @param array $queryArguments
     * @param $payload
     * @return AndroidResponse|mixed
     */
    public function __route()
    {
        if (!$this->__verifyAuthentication()) {
            return self::__unauthorize();
        }
        $method = $this->route->subcontroller . "Route";
        $route = $this->route->subcontroller;
        if (count($this->route->paths) > 0) {
            $route_name = $this->route->subcontroller . "/" . $this->route->path;
        } else {
            $route_name = $this->route->subcontroller;
        }
        if ($this->registersrouter->has($route)) {
            $data = $this->registersrouter->get($route);
            if (isset($data["clazz"]) && isset($data["method"])) {
                $clazz = $data["clazz"];
                $method = $data["method"];
                if (class_exists($data["clazz"])) {
                    $clase = new \ReflectionClass($clazz);
                    $constructor = $clase->newInstance($this);
                    if ($clase->hasMethod($method)) {
                        $result = $constructor->$method(request(), $this->route->paths);
                        if ($result instanceof AndroidResponse) {
                            return $result;
                        }
                        return self::__collection($result);
                    }
                }
            }
        } elseif (method_exists($this, $method)) {
            try {
                $result = call_user_func_array(array($this, $method), [
                    request(), $this->route->paths
                ]);
                if ($result instanceof AndroidResponse) {
                    return $result;
                }
                return self::__collection($result);
            } catch (Exception $ex) {
                return self::__client_error($ex->getMessage());
            }

        } else {
            /*if ($method == "indexRoute") {
                return $this->defaultindexRoute();
            }*/
            return self::__not_found();
        }
    }

    /**
     * @return bool
     */
    private function __verifyAuthentication()
    {
        $class_name = WebserviceKey::getClassFromKey($this->wsKey);
        return $class_name != null;
    }


}
