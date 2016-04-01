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

$r_arr = preg_grep("/o.*$/", $arr);

//	'a' => string 'hello' (length=5)
//	'b' => string 'good' (length=4)

$r_arr = preg_grep("/o.+$/", $arr);

//'b' => string 'good' (length=4)

var_dump($r_arr);