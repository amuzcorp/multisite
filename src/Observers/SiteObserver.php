<?php

namespace Amuz\XePlugin\Multisite\Observers;
use Amuz\XePlugin\Multisite\Models\Site;
use Xpressengine\Config\ConfigManager;
use Xpressengine\Config\ConfigEntity;
use Amuz\XePlugin\Multisite\Controllers\MultisiteSettingsController;
use Xpressengine\Media\Models\Image as MediaImage;

class SiteObserver
{

    /**
     * @var ConfigManager Config 저장소.
     */
    protected $config;

    /**
     * 생성자
     *
     * @param ConfigManager  $config      config manage
     */
    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    public function retrieved(Site $Site)
    {
        $Site->seo = $this->config->get('seo',true,$Site->site_key);
        $Site->config = $this->config->get('site.' . $Site->site_key);
        $Site->plugin = $this->config->get('plugin',false,$Site->site_key);

        //setting meta datas
        $infos = MultisiteSettingsController::getSiteInfos();
        $site_meta = $this->getSiteMeta($Site->site_key);

        $ori_meta = [];
        foreach($site_meta as $k => $v){
            $key = explode(".",$v->name);
            if(!isset($key[1])) continue;
            $ori_meta[$key[1]] = $v;
        }
        if(is_array($infos) && count($infos) > 0){
            foreach($infos as $parent_key => $info){
                $meta[$parent_key] = array();
                foreach($info['fields'] as $children_key => $field){
                    if(array_get($ori_meta, $parent_key) != null && array_get($ori_meta, $parent_key)->get($children_key) != null) {
                        if ($field['_type'] == 'formImage') {
                            if(MediaImage::find($ori_meta[$parent_key]->get($children_key)) != null) {
//                                $field['value'] = $ori_meta[$parent_key]->get($children_key);
                                $field['value'] = MediaImage::find($ori_meta[$parent_key]->get($children_key))->url();
                            }
                        } else {
                            $field['value'] = $ori_meta[$parent_key]->get($children_key);
                        }
                    }
                    $meta[$parent_key][$children_key] = $field;
                }
            }
        }
        $Site->meta = $meta;
    }

    public function getSiteMeta($site_key){
        $site_meta = $this->config->get('site_meta',true,$site_key);
        $site_metas = $this->config->children($site_meta);
        return $site_metas ? $site_metas : [new ConfigEntity()];
    }

    public function restored(Site $Site)
    {
        //
    }
}
