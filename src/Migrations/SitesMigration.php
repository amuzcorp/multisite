<?php

namespace Amuz\XePlugin\Multisite\Migrations;

use Amuz\XePlugin\Multisite\Models\Site;
use Amuz\XePlugin\Multisite\Models\SiteDomain;
use Illuminate\Database\Schema\Blueprint;
use Schema;
use XeDB;
use DB;

class SitesMigration
{
    private $table = 'site_domains';

    /**
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable($this->table)){
            Schema::create($this->table, function (Blueprint $table) {
                $table->engine = "InnoDB";

                $table->string('domain'); // pk
                $table->uuid('site_key'); // fk

                $table->uuid('index_instance')->nullable()->default(null);

                $table->char('is_featured', 1)->default('N');
                $table->char('is_redirect_to_featured', 1)->default('Y');
                $table->char('is_ssl', 1)->default('N');

                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));

                $table->primary('domain');
            });

            /**
             * 도메인 관련정보를 통합적으로 처리
             **/
            $defaultSite = Site::find('default');

            //add Default Site Domains
            $featured_domain = $defaultSite->host;

            //set www
            if(substr($featured_domain, 0, 4) !== 'www.') {
                $alias_domain = 'www.' . $featured_domain;
            }else{
                $alias_domain = substr($featured_domain, 4);
            }

            //도메인 생성
            $defaultSite->Domains()->saveMany([
                new SiteDomain([
                    'domain' => $featured_domain, 'is_ssl' => "N",  'is_featured' => "Y"
                ]),
                new SiteDomain([
                    'domain' => $alias_domain, 'is_ssl' => "N"
                ]),
            ]);

            $defaultSite->save();
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }

    public function tableExists()
    {
        return Schema::hasTable($this->table);
    }
}
