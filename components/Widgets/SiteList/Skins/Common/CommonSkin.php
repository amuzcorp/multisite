<?php
namespace Amuz\XePlugin\Multisite\Components\Widgets\SiteList\Skins\Common;

use View;
use Xpressengine\Skin\GenericSkin;

class CommonSkin extends GenericSkin
{
    /**
     * @var string
     */
    protected static $path = 'multisite/components/Widgets/SiteList/Skins/Common';

    /**
     * 위젯 설정 페이지에 출력할 폼을 출력한다.
     *
     * @param array $args 설정값
     *
     * @return string
     */
    public function renderSetting(array $args = [])
    {
        return $view = View::make(sprintf('%s/views/setting', static::$path), [
            'args'=>$args
        ]);
    }
}
