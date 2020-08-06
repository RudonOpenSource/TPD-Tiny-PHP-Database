<?php

    /**
     * TPD v1.0 [20200806]
     * The Tiny-PHP-Database
     *
     * The most tiny CURD database in a single PHP file,
     * who is designed for no-more-service and the simple data managing.
     * Leave your small PHP projects away from MySQL etc.
     *
     * 全球最轻便的PHP单文件级数据库，CURD支持，OOP设计。
     * 数据直接储存在文件中，方便转移管理。 在无数据库服务的PHP空间中可以给你带来惊喜！
     *
     *
     * @author Rudon<285744011@qq.com>
     * @date 2020-08-06
     *
     *
     * 【特点】
     * 1.纯PHP驱动，无需安装，即放即用；无需后台服务，只占用极少量的I/O资源；
     * 2.TPD密码不限，密码+数据库名为一个组合，决定数据库文件位置，建议使用统一密码；
     * 3.数据库、表、字段等无需提前预设，即插即用；
     * 4.表字段无需提前设计，只需要在插入时全字段插入即可；
     * 5.表字段数据长度、数据类型不限，推荐使用string,int,floor,boolean或者array作为数据类型；
     * 6.TPD数据为明文储存，不适用于机密、金融等项目，请注意数据安全；
     *
     * 【注意】
     * 1.暂不支持join等联表查询，只支持简单的单表CURD操作
     * 2.暂不支持索引、外键、事务、字符集编码选择、comment注释、SQL语句运行
     * 3.暂不支持字段的Auto-Increase自增、not-null非空、union唯一等特点
     * 4.统一参数$where格式为array('column','how', 'target_value'),其中how操作符可选：
     *   =,!=,>,<,>=,<=,'eq','like','in'
     *   当how为in时，参数`target_value`为数组。
     *
     * 【使用方法】
     * 在class TPD后面
     *
     */


    namespace TPD;

    class TPD {
        static private $instance;
        static private $token;

        static public $topFolder;           // src/TPD/data/
        static public $dbFolder;            // src/TPD/data/dbname/0fa279/
        static public $tablePath;           // src/TPD/data/dbname/0fa279/tablename

        static public $dbName = 'dbname';
        static public $tableName;


        private function __construct() {}
        private function __clone() {}


        static public function getInstance($dbname = NULL, $password = '')
        {
            /* 数据库名称自定义，默认是'dbname' */
            if (!is_null($dbname)) {
                self::$dbName = str_replace(' ','', strval($dbname));
            }

            /* 初始化 */
            self::setToken($password);
            self::initEverything();

            /* 获取单例 */
            if (!self::$instance instanceof self) {
                self::$instance = new self();
            }

            /* 返回 */
            return self::$instance;
        }

        /**
         * 设置Token - 关系到数据库文件位置 - 关系到是否能正确读取数据库数据
         * @param string $password
         */
        static public function setToken ($password = '')
        {
            (!empty(self::$token))?NULL: self::$token = self::getToken($password);
        }

        /**
         * 获取Token - 根据password变动
         * @param string $password
         */
        static public function getToken ($password = '')
        {
            return substr(md5(strrev(md5(strval($password)))), 0, 6);
        }

        /**
         * 获取当前Token
         * @return mixed
         */
        public function getCurrentToken()
        {
            return self::$token;
        }

        /**
         * 初始化所有的文件for数据库
         * 包括系统顶级目录，和当前token对应的库文件夹，和表文件
         *
         * 数据库文件夹默认是/src/TPD/data/{dbname}/{token}/
         * 表文件默认在 {数据库文件夹}/{表名}
         * *没有后缀名
         * *
         *
         */
        static function initEverything()
        {
            $sep = DIRECTORY_SEPARATOR;
            /* 默认的总目录，./data/ */
            self::$topFolder = __DIR__ . $sep . 'data' . $sep;
            /* 默认的数据库目录，./data/dbname/token/ */
            self::$dbFolder = self::$topFolder . self::$dbName .$sep. self::$token . $sep;

            if (!is_dir(self::$dbFolder)) {
                mkdir(self::$dbFolder, 0750, true);
            }
        }

        /**
         * 自定义数据库文件总目录路径
         * @param null $path 绝对路径
         * @param boolean $is_clean true代表不保留系统自带的同名数据库目录
         */
        static function setTopFolder ($path = NULL, $is_clean = true)
        {
            if (!is_null($path) && is_dir($path)) {
                $sep = DIRECTORY_SEPARATOR;
                $path = rtrim($path, '/\\').$sep;
                self::$topFolder = $path;
                self::$dbFolder = $path . self::$dbName .$sep. self::$token . $sep;

                if (!is_dir(self::$dbFolder)) {
                    mkdir(self::$dbFolder, 0750, true);
                }

                /* 系统自带的同名数据库目录 */
                if ($is_clean) {
                    $default_db_folder = __DIR__.$sep. 'data' .$sep.self::$dbName.$sep.self::$token.$sep;
                    if (is_dir($default_db_folder)) {
                        self::dirDel($default_db_folder);
                    }
                }
            }
        }

        /**
         * Delete folder with contant (recursive)
         * @param $path
         */
        static public function dirDel($path) {
            $path = rtrim($path, '/');
            $hand = opendir($path);
            while (($file = readdir($hand)) !== false) {
                if ($file == "." || $file == "..")
                    continue;
                if (is_dir($path . "/" . $file)) {
                    self::dirDel($path . "/" . $file);
                } else {
                    @unlink($path . "/" . $file);
                }
            }
            closedir($hand);
            @rmdir($path);
        }

        /**
         * 错误提示
         * @param string $message
         */
        public function showError($message = 'Unknown ERROR!')
        {
            echo $message;
            die();
        }

        /**
         * 获取表文件路径
         * @param string $table
         * @param bool $autoCreate
         * @return string
         */
        public function getTableFilePathByTable ($table = '', $autoCreate = true)
        {
            /* No space is allowed in the path */
            $table = str_replace(' ','', $table);
            if (empty($table)) {
                self::showError('Invalid table name');
            }

            /* Without extension */
            $path = self::$dbFolder . $table;

            /* Auto creating */
            if ($autoCreate && !is_file($path)) {
                file_put_contents($path, json_encode(array()));
            }

            return $path;
        }

        /**
         * 获取表文件路径 - 别名函数
         */
        public function getTP ($table = '', $autoCreate = true) {
            return self::getTableFilePathByTable($table, $autoCreate);
        }

        /**
         * 获取当前表的数据
         * @param string $path
         * @return array
         */
        public function getCurRowsByPath ($path = '') {
            $return = json_decode(file_get_contents($path), true);
            return $return;
        }

        /**
         * 为表 保存新的内容
         * @param string $path
         * @param array $dataArray
         * @return int
         */
        public function savePath($path = '', $dataArray = array())
        {
            file_put_contents($path, json_encode($dataArray));
            return count($dataArray);
        }

        /**
         * 显示所有的数据
         */
        public function showAllData ()
        {
            $data = self::getAllData();
            echo '<h1>All Data</h1>';
            foreach ($data as $oneTableName => $oneTable) {
                echo '<hr/><h3>'.$oneTableName.'</h3>';
                echo '<pre>';
                print_r($oneTable);
                echo '</pre>';
            }
        }

        /**
         * 获取所有的数据 - 以表分开
         * @return array
         */
        public function getAllData ()
        {
            $return = array();
            $tables = self::getTableNames();
            foreach ($tables as $oneT) {
                $return[$oneT] = self::select($oneT);
            }
            return $return;
        }

        /**
         * 获取当前数据库的所有表名字
         * @return array
         */
        public function getTableNames ()
        {
            $return = array();
            $folder = self::$dbFolder;
            $dh = opendir($folder);
            while (($file = readdir($dh)) !== false) {
                if (!in_array($file, array('.','..')) && is_file($folder.$file) && !preg_match('/^\./', $file)) {
                    $return[] = $file;
                }
            }
            closedir($dh);
            return $return;
        }

        /**
         * 数据比较，进行where语句判断
         * @param string $value
         * @param string $how =,!=,>,<,>=,<=,'eq','like','in'
         * @param string $target
         * @return bool
         */
        public function compareValue ($value = '', $how = '=', $target = '')
        {
            $return = false;

            switch (strtolower($how)) {
                case '=':
                case 'eq':
                    if ($value == $target) {
                        $return = true;
                    }
                    break;

                case '!=':
                    if ($value != $target) {
                        $return = true;
                    }
                    break;

                case '!==':
                    if ($value !== $target) {
                        $return = true;
                    }
                    break;

                case '>':
                    if ($value > $target) {
                        $return = true;
                    }
                    break;

                case '<':
                    if ($value < $target) {
                        $return = true;
                    }
                    break;

                case '>=':
                    if ($value >= $target) {
                        $return = true;
                    }
                    break;

                case '<=':
                    if ($value <= $target) {
                        $return = true;
                    }
                    break;

                case 'like':
                    if (stripos($value, $target) !== false) {
                        $return = true;
                    }
                    break;

                case 'in':
                    if(!is_array($target)){
                        self::showError('Invalid array given for case `in`');
                    }
                    if (in_array($value, $target)) {
                        $return = true;
                    }
                    break;

                default:

                    break;
            }
            return $return;
        }




        /**
         * 获取
         * @param string $table
         * @param array $where | column+operation+value，例如'id', '<=', 1
         * @return array
         */
        public function select ($table = '', $where = array())
        {
            $return = array();

            /* 检查where */
            if (!in_array(count($where), array(0, 3))) {
                self::showError('Invalid where...');
            }

            /* 获取+判断 */
            $path = self::getTP($table);
            $now = self::getCurRowsByPath($path);

            if(count($where)){
                /* 筛选 */
                foreach ($now as $oneRow) {
                    if (key_exists($where[0], $oneRow)) {
                        $curV = $oneRow[$where[0]];
                        $target = $where[2];
                        $how = $where[1];
                        if (self::compareValue($curV, $how, $target)) {
                            $return[] = $oneRow;
                        }
                    }
                }
            } else {
                /* 全部 */
                $return = $now;
            }


            return $return;
        }

        /**
         * 插入
         * @param $table string
         * @param $oneRow array
         * @return bool
         */
        public function insert ($table, $oneRow)
        {
            $path = self::getTP($table);
            $now = self::getCurRowsByPath($path);
            $now[] = $oneRow;
            self::savePath($path, $now);
            return true;
        }

        public function bulkInsert ($table = '', $someRows = array()) {
            $path = self::getTP($table);
            $now = self::getCurRowsByPath($path);
            $new = array_merge($now, $someRows);
            self::savePath($path, $new);
            return true;
        }

        /**
         * 删除
         * @param string $table
         * @param array $where
         * @return bool
         */
        public function delete ($table = '', $where = array())
        {
            /* 检查where */
            if (!in_array(count($where), array(0, 3))) {
                self::showError('Invalid where...');
            }

            /* 清空表 */
            if(count($where) == 0){
                $path = self::getTP($table);
                file_put_contents($path, json_encode(array()));
                return true;
            }


            /* 获取+判断 */
            $path = self::getTP($table);
            $now = self::getCurRowsByPath($path);

            foreach ($now as $key=>$oneRow) {
                if (key_exists($where[0], $oneRow)) {
                    $curV = $oneRow[$where[0]];
                    $target = $where[2];
                    $how = $where[1];
                    if (self::compareValue($curV, $how, $target)) {
                        unset($now[$key]);
                    }
                }
            }


            self::savePath($path, $now);
            return true;
        }

        /**
         * 更新
         * @param string $table
         * @param array $where 当where为空时，当前表所有的数据都要更新
         * @param array $toBe
         */
        public function update ($table = '', $where = array(), $toBe = array())
        {
            /* 检查where */
            if (!in_array(count($where), array(0, 3))) {
                self::showError('Invalid where...');
            }

            /* 获取+判断 */
            $path = self::getTP($table);
            $now = self::getCurRowsByPath($path);

            if(count($where)){
                /* 筛选 */
                foreach ($now as $key=>$oneRow) {
                    if (key_exists($where[0], $oneRow)) {
                        $curV = $oneRow[$where[0]];
                        $target = $where[2];
                        $how = $where[1];
                        if (self::compareValue($curV, $how, $target)) {
                            $newData = $oneRow;
                            foreach ($toBe as $k=>$v){
                                if (key_exists($k, $newData)) {
                                    $newData[$k] = $v;
                                }
                            }
                            $now[$key] = $newData;
                        }
                    }
                }
            } else {
                /* 全都更新 */
                foreach ($now as $key=>$oneRow) {
                    $newData = $oneRow;
                    foreach ($toBe as $k=>$v){
                        if (key_exists($k, $newData)) {
                            $newData[$k] = $v;
                        }
                    }
                    $now[$key] = $newData;
                }
            }


            self::savePath($path, $now);
            return true;
        }



    }/* END OF CLASS TPD */






/**
 * =================== 使用方法 ====================
 *
 * include dirname(__DIR__).'/src/TPD/TPD.php';
 * use TPD;
 * $tpd = TPD\TPD::getInstance('DBname', 'password');
 * $tpd->setTopFolder('/full/path/of/top/folder/for/DB/');
 *
 * // 查看所有表和数据
 * $tpd->showAllData();
 *
 * $table = 'student';
 * $oneRow = array( 'id' => 1, 'name' => 'Jack', 'age' => 10 );
 * $someRows = array(
 *      array( 'id' => 3, 'name' => 'Peter', 'age' => 8 ),
 *      array( 'id' => 4, 'name' => 'Lucy', 'age' => 9 )
 * );
 *
 * // 0。清空表
 * $tpd->delete($table);
 *
 * // 1.1 插入
 * $tpd->insert($table, $oneRow);
 *
 * // 1.2 批量插入
 * $tpd->bulkInsert($table, $someRows);
 *
 * // 2. 查询
 * $result1 = $tpd->select($table, array('id', '=', 1));
 * $result2 = $tpd->select($table, array('id', '>', 2));
 * $result3 = $tpd->select($table, array('name', 'like', 'ter'));
 * $result4 = $tpd->select($table, array('id', 'in', array(2,3,4)));
 * $result5 = $tpd->select($table);
 *
 * // 3. 更新
 * $where = array( 'id', '=', 3 );
 * $toBe = array( 'name' => 'NEW-NAME', 'age'=>80 );
 * $tpd->update($table, $where, $toBe);

 * // 4 删除
 * $where = array( 'id', '<', 2 );
 * $tpd->delete($table, $where);

 * $where = array( 'id', 'in', array(1,5) );
 * $tpd->delete($table, $where);
 *
 */


