<?php

    /**
     * 简单测试步骤
     * 1. include /path/to/src/TPD/TPD.php
     * 2. use TPD;
     * 3. new TPD();
     * 4. CURD
     */

    namespace demo;
    include dirname(__DIR__).'/src/TPD/TPD.php';
    use TPD;

    class Tester {
        public $tpd;

        public function __construct()
        {
            /**
             * 单例模式
             * 第一个参数是dbname [string]   -- 可选 -- 默认字符串'dbname'
             * 第二个参数是password [string] -- 可选 -- 只有每次使用统一的密码才能取到正确的数据，默认是空字符串
             */
            $this->tpd = TPD\TPD::getInstance('school');

            /**
             * 自定义顶级的数据库文件夹的储存的绝对路径
             * 默认是 /src/TPD/data/
             */
            //$this->tpd->setTopFolder('/full/path/of/top/folder/for/DB/');
            $this->tpd->setTopFolder('/Volumes/Park/系统基地/Work/data');
        }

        /**
         * CURD测试
         */
        public function test()
        {
            /* 测试数据 */
            $table = 'student';

            $oneRow = array(
                'id' => 1,
                'name' => 'Jack',
                'age' => 10
            );

            $someRows = array(
                array(
                    'id' => 3,
                    'name' => 'Peter',
                    'age' => 8
                ),
                array(
                    'id' => 4,
                    'name' => 'Lucy',
                    'age' => 9
                ),
                array(
                    'id' => 5,
                    'name' => 'Kim',
                    'age' => 11
                )
            );

            /* 清空表 */
            $this->tpd->delete($table);

            /* 1.1 插入 */
            $this->tpd->insert($table, $oneRow);

            /* 1.2 批量插入 */
            $this->tpd->bulkInsert($table, $someRows);

            /* 2. 查询 */
            $result1 = $this->tpd->select($table, array('id', '=', 1));
            $result2 = $this->tpd->select($table, array('id', '>', 2));
            $result3 = $this->tpd->select($table, array('name', 'like', 'ter')); // contains
            $result4 = $this->tpd->select($table);

            echo '<hr />';
            print_r($result1);
            echo '<hr />';
            print_r($result2);
            echo '<hr />';
            print_r($result3);
            echo '<hr />';
            print_r($result4);


            /* 3. 更新 */
            $where = array(
                'id',
                '=',
                3
            );
            $toBe = array(
                'name' => 'NEW-NAME',
                'age'=>80
            );
            $this->tpd->update($table, $where, $toBe);

            /* 4 删除 */
            $where = array(
                'id',
                '<',
                2
            );
            $this->tpd->delete($table, $where);

            $where = array(
                'id',
                'in',
                array(1,5)
            );
            $this->tpd->delete($table, $where);
            //print_r($this->tpd->select($table));die();
        }


        public function showAllData () {
            $this->tpd->showAllData();
        }

    }


    $obj = new Tester();
    //$obj->test();
    $obj->showAllData();