<?php

namespace Amuz\XePlugin\Multisite\Observers;
use Amuz\XePlugin\Multisite\Models\Site;
use Xpressengine\Config\ConfigManager;
use Xpressengine\Config\ConfigEntity;

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

        $site_meta = $this->getSiteMeta($Site->site_key);
        $meta = [];
        foreach($site_meta as $k => $v){
            $key = explode(".",$v->name);
            if(!isset($key[1])) continue;
            $meta[$key[1]] = $v;
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
