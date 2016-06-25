<?php
namespace Anam\Aba;

use Illuminate\Support\ServiceProvider;

class AbaServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('aba', function() {
            return new \Anam\Aba\Aba;
        });
    }
}
