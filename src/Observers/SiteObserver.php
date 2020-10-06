<?php

namespace Amuz\XePlugin\Multisite\Observers;
use Amuz\XePlugin\Multisite\Models\Site;

class SiteObserver
{
    public function retrieved(Site $Site)
    {
        $Site->seo = app('xe.config')->get('seo',false,$Site->site_key);
        $Site->config = app('xe.site')->getSiteConfig($Site->site_key);
    }

    public function restored(Site $Site)
    {
        //
    }
}
