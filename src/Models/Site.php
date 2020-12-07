<?php
/**
 * Site
 *
 * PHP version 7
 *
 * @category    Site
 * @package     Amuz\XePlugin\Site
 * @author      xiso <xiso@amuz.co.kr>
 * @copyright   2020 Copyright Amuz Corp. <https://amuz.co.kr>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://amuz.co.kr
 */
namespace Amuz\XePlugin\Multisite\Models;
use Xpressengine\Site\Site as XeSite;


class Site extends XeSite
{
    protected $attributes;

    /**
     * Site constructor.
     *
     * @param array $attributes load default sites config
     */
    public function __construct(array $attributes = array())
    {
        $this->attributes = $attributes;
    }

    public function Domains()
    {
        return $this->hasMany('Amuz\XePlugin\Multisite\Models\SiteDomain','site_key')->orderBy('is_featured','DESC')->orderBy('is_ssl','DESC')->orderBy('is_redirect_to_featured','DESC');
    }

    public function Configs()
    {
        return $this->hasMany('Amuz\XePlugin\Multisite\Models\SiteConfig','site_key');
    }

    public function configSEO(){
        return $this->hasMany('Amuz\XePlugin\Multisite\Models\SiteConfig','site_key')->where('name','seo');
    }

    public function FeaturedDomain()
    {
        return $this->Domains()->where('is_featured','=','Y');
    }

    public function getDomainLink($text = null, $param = false){
        $domain = $this->FeaturedDomain->first();
        if($text === null) $text = $domain->domain;

        $link = $domain->is_ssl == "Y" ? "https://" : "http://";
        if($text === false){
            return sprintf("%s/%s",$link . $domain->domain,$param);
        }else{
            return sprintf("<a href='%s/%s' target='_blank'>%s</a>",$link . $domain->domain,$param,$text);
        }

    }
}
