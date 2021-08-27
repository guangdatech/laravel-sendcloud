<?php
/**
 * SendCloudProvider.php
 *
 * @copyright  2021 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2021-08-27 16:51:27
 * @modified   2021-08-27 16:51:27
 */

namespace Guangda\LaravelSendCloud;

use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider;

class SendCloudProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/config/services.php', 'services'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/config/mail.php', 'mail.mailers'
        );

        $this->app[MailManager::class]->extend('sendcloud', function () {
            $apiUser = $this->app['config']->get('services.sendcloud.api_user');
            $apiKey = $this->app['config']->get('services.sendcloud.api_key');
            return new SendCloudTransport($apiUser, $apiKey);
        });
    }
}
