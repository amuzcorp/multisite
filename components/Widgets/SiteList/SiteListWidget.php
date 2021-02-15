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
        $Sites = Site::limit(array_get($this->config, 'latest_count',10))->get();
        return $this->renderSkin([
            'Sites' => $Sites,
            'title' => $title
        ]);
    }

    public function renderSetting(array $args = [])
    {
        // 출력할 사이트 개수
        return uio('formText', ['name'=>'latest_count', 'value'=>array_get($args, 'latest_count'), 'label'=>'출력할 개수', 'description'=>'출력할 사이트 개수를 입력합니다.']);
    }

    public function resolveSetting(array $inputs = [])
    {
        if(!is_numeric(array_get($inputs, 'latest_count'))){
            throw new ValidationException();
        }

        return $inputs;
    }
}
