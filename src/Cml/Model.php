<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 系统默认Model
 * *********************************************************** */
namespace Cml;

/**
 * 基础Model类，在CmlPHP中负责数据的存取(目前包含db/cache)
 * @package Cml
 */
class Model
{
    /**
     * @var null 表前缀
     */
    protected $tablePrefix = null;

    /**
     * @var null 表名
     */
    protected $table = null;

    /**
     * @var array Db驱动实例
     */
    private static $dbInstance = array();

    /**
     * @var array Cache驱动实例
     */
    private static $cacheInstance = array();

    /**
     * 获取db实例
     *
     * @param string $conf 使用的数据库配置;
     *
     * @return \Cml\Db\MySql\Pdo | \Cml\Db\MongoDB\MongoDB
     */
    public function db($conf = '')
    {
        $conf == '' &&  $conf = $this->getDbConf();
        if (is_array($conf)) {
            $config = $conf;
            $conf = md5(json_encode($conf));
        } else {
            $config = Config::get($conf);
        }
        $driver = '\Cml\Db\\'.str_replace('.', '\\', $config['driver']);
        if (isset(self::$dbInstance[$conf])) {
            return self::$dbInstance[$conf];
        } else {
            self::$dbInstance[$conf] = new $driver($config);
            return self::$dbInstance[$conf];
        }
    }

    /**
     * 当程序连接N个db的时候用于释放于用连接以节省内存
     *
     * @param string $conf 使用的数据库配置;
     */
    public function closeDb($conf = 'default_db')
    {
        //$this->db($conf)->close();释放对象时会执行析构回收
        unset(self::$dbInstance[$conf]);
    }

    /**
     * 获取cache实例
     *
     * @param string $conf 使用的缓存配置;
     *
     * @return \Cml\Cache\Redis | \Cml\Cache\Apc | \Cml\Cache\File | \Cml\Cache\Memcache
     */
    public function cache($conf = 'default_cache')
    {
        if (is_array($conf)) {
            $config = $conf;
            $conf = md5(json_encode($conf));
        } else {
            $config = Config::get($conf);
        }

        $driver = '\Cml\Cache\\'.$config['driver'];
        if (isset(self::$cacheInstance[$conf])) {
            return self::$cacheInstance[$conf];
        } else {
            if ($config['on']) {
                self::$cacheInstance[$conf] = new $driver($config);
                return self::$cacheInstance[$conf];
            } else {
                throwException(Lang::get('_NOT_OPEN_', $conf));
                return false;
            }
        }
    }

    /**
     * 初始化一个Model实例
     *
     * @return \Cml\Model
     */
    public static function getInstance()
    {
        static $mInstance = array();
        $class = get_called_class();
        if (!isset($mInstance[$class])) {
            $mInstance[$class] = new $class();
        }
        return $mInstance[$class];
    }

    /**
     * 获取表名
     *
     * @return string
     */
    public function getTableName()
    {
        if (is_null($this->table)) {
            $tmp = get_class($this);
            $this->table = strtolower(substr($tmp, strrpos($tmp, '\\') + 1, -5));
        }
        return $this->table;
    }

    /**
     * 通过某个字段获取单条数据-快捷方法
     *
     * @param mixed $val 值
     * @param string $column 字段名 不传会自动分析表结构获取主键
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     *
     * @return bool|array
     */
    public function getByColumn($val, $column = null, $tableName = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($column) && $column = $this->db($this->getDbConf())->getPk($tableName);
        $data = $this->db($this->getDbConf())->table($tableName, $this->tablePrefix)
            ->where($column, $val)
            ->limit(0, 1)
            ->select();
        if (isset($data[0])) {
            return $data[0];
        } else {
            return false;
        }
    }

    /**
     * 通过某个字段获取多条数据-快捷方法
     *
     * @param mixed $val 值
     * @param string $column 字段名 不传会自动分析表结构获取主键
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     *
     * @return bool|array
     */
    public function getMultiByColumn($val, $column = null, $tableName = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($column) && $column = $this->db($this->getDbConf())->getPk($tableName);
        return $this->db($this->getDbConf())->table($tableName, $this->tablePrefix)
            ->where($column, $val)
            ->select();
    }

    /**
     * 增加一条数据-快捷方法
     *
     * @param array $data 要新增的数据
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     *
     * @return int
     */
    public function set($data, $tableName = null){
        is_null($tableName) && $tableName = $this->getTableName();
        return $this->db($this->getDbConf())->set($tableName, $data);
    }

    /**
     * 通过字段更新数据-快捷方法
     *
     * @param int $val 字段值
     * @param array $data 更新的数据
     * @param string $column 字段名 不传会自动分析表结构获取主键
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     *
     * @return bool
     */
    public function updateByColumn($val, $data, $column = null, $tableName = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($column) && $column = $this->db($this->getDbConf())->getPk($tableName);
        return $this->db($this->getDbConf())->where($column, $val)
            ->update($tableName, $data);
    }

    /**
     * 通过主键删除数据-快捷方法
     *
     * @param mixed $val
     * @param string $column 字段名 不传会自动分析表结构获取主键
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     *
     * @return bool
     */
    public function delByColumn($val, $column = null, $tableName = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($column) && $column = $this->db($this->getDbConf())->getPk($tableName);
        return $this->db($this->getDbConf())->where($column, $val)
            ->delete($tableName);
    }

    /**
     * 获取数据的总数
     *
     * @param null $pkField 主键的字段名
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     *
     * @return mixed
     */
    public function getTotalNums($pkField = null, $tableName = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_null($pkField) && $pkField = $this->db($this->getDbConf())->getPk($tableName);
        return $this->db($this->getDbConf())->table($tableName, $this->tablePrefix)->count($pkField);
    }

    /**
     * 获取数据列表
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     * @param string|array $order 传asc 或 desc 自动取主键 或 ['id'=>'desc', 'status' => 'asc']
     * @param string $tableName 表名 不传会自动从当前Model中$table属性获取
     *
     * @return array
     */
    public function getList($offset = 0, $limit = 20, $order = 'DESC', $tableName = null)
    {
        is_null($tableName) && $tableName = $this->getTableName();
        is_array($order) || $order = array($this->db($this->getDbConf())->getPk($tableName) => $order);

        $dbInstance = $this->db($this->getDbConf())->table($tableName, $this->tablePrefix);
        foreach($order as $key => $val)  {
            $dbInstance->orderBy($key, $val);
        }
        return $dbInstance->limit($offset, $limit)
            ->select();
    }

    /**
     * 获取当前Model的数据库配置串
     *
     * @return string
     */
    public function getDbConf()
    {
        return property_exists($this, 'db') ? $this->db : 'default_db';
    }
}