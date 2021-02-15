<?php
namespace Amuz\XePlugin\Multisite\Components\Widgets\SiteList;

use Amuz\XePlugin\Multisite\Models\Site;
use Illuminate\Validation\ValidationException;

class SiteListWidget extends \Xpressengine\Widget\AbstractWidget
{
    protected static $path = 'multisite/components/Widgets/SiteList';

    public function render()
    {
        $widgetConfig = $this->setting();
        $title = $widgetConfig['@attributes']['title'];
        $Sites = Site::whereDoesntHave('configMeta', function($query){
            $query->where('vars','like','%"use_list":"N"%');
        })->limit(array_get($this->config, 'latest_count',10))->get();
        return $this->renderSkin([
            'Sites' => $Sites,
            'target_instance' => array_get($this->config, 'target_instance',10),
            'title' => $title
        ]);
    }

    public function renderSetting(array $args = [])
    {
        $instance_list = \DB::table('instance_route')->where('site_key', '=', \XeSite::getCurrentSiteKey())->where('module','module/multisite@sitelist')->get();
        $options = array();
        foreach($instance_list as $instance){
            $options[$instance->instance_id] = $instance->url;
        }
        // 출력할 사이트 개수
        $settings = '';
        $settings .= uio('formSelect', ['name'=>'target_instance', 'value'=>array_get($args, 'target_instance'), 'options' => $options, 'label'=>'대상 인스턴스', 'description'=>'링크 대상이되는 인스턴스를 선택합니다.']);
        $settings .= uio('formText', ['name'=>'latest_count', 'value'=>array_get($args, 'latest_count'), 'label'=>'출력할 개수', 'description'=>'출력할 사이트 개수를 입력합니다.']);

        return $settings;
    }

    public function resolveSetting(array $inputs = [])
    {
        if(!is_numeric(array_get($inputs, 'latest_count'))){
            throw new ValidationException();
        }

        return $inputs;
    }
}
