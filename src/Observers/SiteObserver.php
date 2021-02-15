<?php

namespace Amuz\XePlugin\Multisite\Observers;
use Amuz\XePlugin\Multisite\Models\Site;
use Xpressengine\Config\ConfigManager;

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

        $site_meta = $this->config->get('site_meta',true,$Site->site_key);
        $site_metas = $this->config->children($site_meta);
        if(!isset($site_metas[0])) $site_metas = [0 => $this->config];
        $Site->meta = $site_metas[0];
    }

    public function restored(Site $Site)
    {
        //
    }
}
