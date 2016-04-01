<?php
/**
 * Created by PhpStorm.
 * User: sks
 * Date: 2016/4/1
 * Time: 16:36
 */

$arr = array(
    'a' => 'hello',
    'b' => 'good',
    'c' => 'happay',
);

$r_arr = preg_grep("/^o.+$/", $arr);

var_dump($r_arr);