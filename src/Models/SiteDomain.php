<?php

namespace Amuz\XePlugin\Multisite\Models;
use Xpressengine\Database\Eloquent\DynamicModel;
use Xpressengine\Menu\Models\MenuItem;
use Xpressengine\Routing\InstanceRoute;

class SiteDomain extends DynamicModel
{
    protected $guarded = [];
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'site_domains';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'domain';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    public function get($key){
        if(!isset($this->attributes[$key])) return false;
        return $this->attributes[$key];
    }

    public function Site(){
        return $this->belongsTo('Amuz\XePlugin\Multisite\Models\Site','site_key');
    }


    /**
     * Instance route relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function InstanceRoute()
    {
        return $this->hasOne(InstanceRoute::class, 'instance_id', 'index_instance');
    }

    /**
     * Aggregator relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function MenuItem()
    {
        return $this->hasOne(MenuItem::class,'id','index_instance');
    }
}
