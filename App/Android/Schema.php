<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 10/01/2019
 * Time: 3:38
 */

namespace App\Android;


use App\Database\Schema\Blueprint;
use App\Database\Schema\Grammars\MySqlGrammar;
use Closure;


class Schema
{
    public static function getDb()
    {
        return \Db::getInstance(_PS_USE_SQL_SLAVE_);
    }

    private static function createBlueprint($table, Closure $callback = null)
    {
        return new Blueprint($table, $callback);
    }

    /**
     * @param $table
     * @param Closure $callback
     * @return bool
     */
    public static function create($table, Closure $callback)
    {
        $table = _DB_PREFIX_ . $table;
        return self::build(tap(self::createBlueprint($table), function (Blueprint $blueprint) use ($callback) {
            $blueprint->create();
            $callback($blueprint);
        }));
    }

    public static function execute($query)
    {
        return self::getDb()->execute($query);
    }

    public static function deletePrimaryKeyAndAdd($table, $primaryKeys)
    {

        $keys = [];
        if (is_array($primaryKeys)) {
            foreach ($primaryKeys as $key) {
                $keys[] = "`$key`";
            }
        }
        if ($keys > 0) {
            return self::getDb()->execute('ALTER TABLE `' . _DB_PREFIX_ . $table . '`
        DROP PRIMARY KEY,
        ADD PRIMARY KEY (' . implode(',', $keys) . ')');
        }
        return true;
    }

    /**
     * @param $table
     * @param Closure $callback
     * @return bool
     */
    public static function table($table, Closure $callback)
    {
        $table = _DB_PREFIX_ . $table;
        return self::build(tap(self::createBlueprint($table), function (Blueprint $blueprint) use ($callback) {
            $callback($blueprint);
        }));
    }

    public static function deleteTable($table)
    {
        return self::getDb()->execute('
            DROP TABLE IF EXISTS 
              `' . _DB_PREFIX_ . $table . '`;
        ');
    }

    private static function getGrammar()
    {

        $grammar = new MySqlGrammar([
            'driver' => 'mysql',
            'host' => _DB_SERVER_,
            'port' => '3306',
            'database' => _DB_NAME_,
            'username' => _DB_USER_,
            'password' => _DB_PASSWD_,
            'unix_socket' => '',
            'prefix_indexes' => true,
            'charset' => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix' => _DB_PREFIX_,
            'strict' => true,
            'engine' => _MYSQL_ENGINE_,
        ]);
        return $grammar;
    }

    /**
     * @param Blueprint $blueprint
     * @return bool
     */
    private static function build(Blueprint $blueprint)
    {
        $res = true;
        $sqls = $blueprint->toSql(self::getGrammar());
        foreach ($sqls as $sql) {
            $res &= self::getDb()->execute($sql);
        }
        return $res >= 1;
    }
}
