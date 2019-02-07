<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 18/01/2019
 * Time: 11:51
 */

namespace App;


use PrestaShop\PrestaShop\Adapter\Entity\Validate;

class AndroidCustomer
{
    /**
     * @var Support\Collection
     */
    private $attributes;
    /**
     * @var Support\Collection
     */
    private $errores = [];

    /**
     * @var array
     */
    private $requires = [
        "lastname",
        "firstname",
        "email",
        "passwd"
    ];

    /**
     * AndroidCustomer constructor.
     * @param array $params
     */
    public function __construct($params = array())
    {
        $this->setAttributes($params);
    }

    public function Login($email, $passwd)
    {
        $customer = new \Customer();
        $customer->getByEmail($email, $passwd);
        return $this->authResponse($customer);
    }

    /**
     * @param array $params
     */
    public function setAttributes($params = array())
    {
        $this->attributes = collect($params);
        $this->errores = collect();
    }

    /**
     * @param $email
     * @return bool
     * @throws \PrestaShopDatabaseException
     */
    function emailExist($email)
    {
        $sql = new \DbQuery();
        $sql = $sql->select("count(c.`email`) as count")
            ->from("customer", "c")
            ->where("c.`email`='$email'")
            ->limit(1);

        $rows = \Db::getInstance()->executeS($sql);

        $count = (int)$rows[0]["count"];
        return $count > 0;
    }


    /**
     * @return bool
     */
    public function validate($no_verify_email = false)
    {
        $this->errores->clear();
        foreach ($this->requires as $key) {
            if ($this->attributes->has($key)) {
                if (empty($this->attributes->get($key))) {
                    $this->errores->push("El campo $key esta vacio.");
                }
            } else {
                $this->errores->push("El campo $key esta vacio.");
            }
        }
        if (!Validate::isPlaintextPassword($this->attributes->get("passwd"))) {
            $this->errores->push("La cantraseña es invalida '>5 y <72'");
        }
        if (!$no_verify_email) {
            if (Validate::isEmail($this->attributes->get("email"))) {
                if (!$this->emailExist($this->attributes->get("email"))) {
                    return true;
                } else {
                    $email = $this->attributes->get("email");
                    $this->errores->push("La dirección de correo electrónico \"$email\" ya está en uso, por favor, elija otra para iniciar sesión o registrarse");
                }
            } else {
                $this->errores->push("Ingrese un email valido.");
            }
        } else {
            if ($this->errores->count() <= 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function create()
    {
        $customer = new \Customer();
        if ($this->validate()) {
            $this->attributes->put("passwd", \Tools::hash($this->attributes->get("passwd")));
            foreach ($this->attributes->toArray() as $key => $value) {
                $customer->{$key} = $value;
            }
            if ($customer->add()) {
                return $this->authResponse($customer);
            }
        }
        return $this->createResponse(false, null);
    }

    /**
     * @param $id
     * @return array
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function update($id)
    {
        $customer = new \Customer($id);
        // unset($this->requires['passwd']);
        if ($this->validate(true)) {
            //$this->attributes->put("passwd", \Tools::hash($this->attributes->get("passwd")));
            foreach ($this->attributes->toArray() as $key => $value) {
                if ($key != "new_passwd") {
                    if ($key == "passwd") {
                        $old = $value;
                        $new = $this->attributes->get("new_passwd");
                        if ($old == $new) {
                            $customer->{$key} = \Tools::hash($new);
                        }
                    } else {
                        $customer->{$key} = $value;
                    }
                }
            }
            if ($customer->update()) {
                return $this->authResponse($customer);
            }
        }
        return $this->createResponse(false, null);
    }

    public function authResponse($customer)
    {
        if (Validate::isLoadedObject($customer)) {
            $filds = $customer->getFields();
            unset($filds['passwd']);
            unset($filds['reset_password_token']);
            unset($filds['reset_password_validity']);
            unset($filds['last_passwd_gen']);
            unset($filds['newsletter_date_add']);
            unset($filds['ip_registration_newsletter']);
            //unset($filds['optin']);
            unset($filds['siret']);
            unset($filds['ape']);
            unset($filds['outstanding_allow_amount']);
            unset($filds['show_public_prices']);
            unset($filds['id_risk']);
            // unset($filds['newsletter']);
            if (!isset($filds['id_customer'])) {
                $filds['id_customer'] = $filds['id'];
            }
            return $this->createResponse(true, $filds);
        } else {
            return $this->createResponse(false, null);
        }
    }

    function createResponse($login, $customer = null)
    {
        return array(
            "login" => $login,
            "customer" => $customer,
            "message" => $this->errores->first(),
            "messages" => $this->errores->toArray()
        );
    }

    /**
     * @return Support\Collection
     */
    public function getErrors()
    {
        return $this->errores;
    }

}
