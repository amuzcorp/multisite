<?php
namespace Amuz\XePlugin\Multisite;

/**
 * * Class Resources
 * @package Amuz\XePlugin\Multisite
 */
class Resources{

    public static function setSiteInfo()
    {
        \XeRegister::push('multisite/site_info', 'setting.site_list', [
            'title' => '사이트목록',
            'description' => '사이트 목록에 제공될 정보를 설정합니다.',
            'display' => true,
            'ordering' => 50
        ]);
        \XeRegister::push('multisite/site_info', 'setting.site_list.use_list', [
            "_type" => "formSelect",
            "size" => "col-sm-12",
            "uio" => ['name'=>'use_list', 'label'=>'사이트목록에 출력', 'options' => [
                'Y'=>'출력함',
                'N'=>'출력안함'
            ]]
        ]);
    }
}
