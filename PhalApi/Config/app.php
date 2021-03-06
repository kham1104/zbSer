<?php
/**
 * 请在下面放置任何您需要的应用配置
 * 直播开发联系QQ：172-994-3308
 */

return array(

    /**
     * 应用接口层的统一参数
     */
    'apiCommonRules' => array(
        //'sign' => array('name' => 'sign', 'require' => true),
    ),
    'REDIS_HOST' => "127.0.0.1",
    'REDIS_AUTH' => "qq123123",
    'REDIS_PORT' => "6379",
    
    'sign_key' => '76576076c1f5f657b634e966c8836a06',
		
	'uptype'=>2,//上传方式：1表示 七牛，2表示 本地
		/**
     * 七牛相关配置
     */
    'Qiniu' =>  array(
        //统一的key
        'accessKey' =>'iFpWjfkdjsfhjdhsjkjCZmjy0',
        'secretKey' =>'0sZpfjdlksjfkldsjfkl88vKTD9',
        //自定义配置的空间
        'space_bucket' =>'',
        'space_host' =>'',
        'uphost' => 'http://up.qiniup.com', //区域上传域名(服务端)  
    ),
		
		 /**
     * 本地上传
     */
    'UCloudEngine' => 'local',

    /**
     * 本地存储相关配置（UCloudEngine为local时的配置）
     */
    'UCloud' => array(
        //对应的文件路径
        'host' => 'http://ccc.hulu678.com/upload',
        'domain' => 'http://ccc.hulu678.com'
    ),
		
		/**
     * 云上传引擎,支持local,oss,upyun
     */
    //'UCloudEngine' => 'oss',

    /**
     * 云上传对应引擎相关配置
     * 如果UCloudEngine不为local,则需要按以下配置
     */
   /*  'UCloud' => array(
        //上传的API地址,不带http://,以下api为阿里云OSS杭州节点
        'api' => 'oss-cn-hangzhou.aliyuncs.com',

        //统一的key
        'accessKey' => '',
        'secretKey' => '',

        //自定义配置的空间
        'bucket' => '',
        'host' => 'http://image.xxx.com', //必带http:// 末尾不带/

        'timeout' => 90
    ), */
		

		
);
