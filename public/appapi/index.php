<?php
/**
 * Appapi 统一入口
 */

require_once dirname(__FILE__) . '/../../PhalApi/public/init.php';
//装载你的接口
DI()->loader->addDirs('Appapi');

/** ---------------- 响应接口请求 ---------------- **/

$api = new PhalApi();
$rs = $api->response();
$rs->output();

