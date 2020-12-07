<?php

namespace Amuz\XePlugin\Multisite\Models;
use Xpressengine\Database\Eloquent\DynamicModel;

class SiteConfig extends DynamicModel
{
    protected $guarded = [];
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'config';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = ['site_key','name'];

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
    public $timestamps = false;

    public function Site(){
        return $this->belongsTo('Amuz\XePlugin\Multisite\Models\Site','site_key');
    }
}
