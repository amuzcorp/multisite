<?php

namespace Amuz\XePlugin\Multisite\Components\Modules\SiteList;

use Route;
use XeSkin;
use View;
use Xpressengine\Menu\AbstractModule;
use Amuz\XePlugin\Multisite\Middleware\Cors;

class SiteListModule extends AbstractModule
{
    /**
     * boot
     *
     * @return void
     */
    public static function boot()
    {
        self::registerInstanceRoute();
    }

    /**
     * Register Plugin Instance Route
     *
     * @return void
     */
    protected static function registerInstanceRoute()
    {
        Route::instance(self::getId(), function () {
            Route::get('/', ['as' => 'index', 'uses' => 'MultisiteController@index', 'middleware' => [Cors::class]]);
            Route::get('/{site_key}', ['as' => 'show', 'uses' => 'MultisiteController@show', 'middleware' => [Cors::class]]);
        }, ['namespace' => 'Amuz\XePlugin\Multisite\Controllers']);

    }

    /**
     * Return Create Form View
     * @return mixed
     */
    public function createMenuForm()
    {
        $skins = XeSkin::getList('module/multisite@sitelist');

        return View::make('multisite::components/Modules/SiteList/views/create', [
            'skins' => $skins
        ])->render();
    }

    /**
     * Process to Store
     *
     * @param string $instanceId to store instance id
     * @param array $menuTypeParams for menu type store param array
     * @param array $itemParams except menu type param array
     *
     * @return mixed
     * @internal param $inputs
     *
     */
    public function storeMenu($instanceId, $menuTypeParams, $itemParams)
    {
        //
    }

    /**
     * Return Edit Form View
     *
     * @param string $instanceId to edit instance id
     *
     * @return mixed
     */
    public function editMenuForm($instanceId)
    {
        $skins = XeSkin::getList(self::getId());

        return View::make('multisite::components/Modules/SiteList/views/edit', [
            'instanceId' => $instanceId,
            'skins' => $skins
        ])->render();
    }

    /**
     * Process to Update
     *
     * @param string $instanceId to update instance id
     * @param array $menuTypeParams for menu type update param array
     * @param array $itemParams except menu type param array
     *
     * @return mixed
     * @internal param $inputs
     *
     */
    public function updateMenu($instanceId, $menuTypeParams, $itemParams)
    {
        //
    }

    /**
     * displayed message when menu is deleted.
     *
     * @param string $instanceId to summary before deletion instance id
     *
     * @return string
     */
    public function summary($instanceId)
    {
        // TODO: Implement summary() method.
    }

    /**
     * Process to delete
     *
     * @param string $instanceId to delete instance id
     *
     * @return mixed
     */
    public function deleteMenu($instanceId)
    {
        // TODO: Implement deleteMenu() method.
    }

    /**
     * Return URL about module's detail setting
     * getInstanceSettingURI
     *
     * @param string $instanceId instance id
     * @return mixed
     */
    public static function getInstanceSettingURI($instanceId)
    {
        return route('manage.multisite.edit', $instanceId);
    }

    /**
     * Get menu type's item object
     *
     * @param string $id item id of menu type
     * @return mixed
     */
    public function getTypeItem($id)
    {
        // TODO: Implement getTypeItem() method.
    }
}
