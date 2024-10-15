<?php

namespace App\Client;

use App\Client\Http\Controllers\ClearLogsController;
use App\Client\Http\Controllers\CreateTunnelController;
use App\Client\Http\Controllers\DashboardController;
use App\Client\Http\Controllers\GetTunnelsController;
use App\Client\Http\Controllers\LogController;
use App\Client\Http\Controllers\PushLogsToDashboardController;
use App\Client\Http\Controllers\ReplayLogController;
use App\Http\App;
use App\Http\ClientRouteGenerator;
use App\WebSockets\Socket;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

class Factory
{
    /** @var string */
    protected $host = 'localhost';

    /** @var int */
    protected $port = 8080;

    /** @var string */
    protected $auth = '';

    /** @var string */
    protected $basicAuth;

    /** @var \React\EventLoop\LoopInterface */
    protected $loop;

    /** @var App */
    protected $app;

    /** @var ClientRouteGenerator */
    protected $router;

    public function __construct()
    {
        $this->loop = Loop::get();
        $this->router = new ClientRouteGenerator();
    }

    public function setHost(string $host)
    {
        $this->host = $host;

        return $this;
    }

    public function setPort(int $port)
    {
        $this->port = $port;

        return $this;
    }

    public function setAuth(?string $auth)
    {
        $this->auth = $auth;

        return $this;
    }

    public function setBasicAuth(?string $basicAuth)
    {
        $this->basicAuth = $basicAuth;

        return $this;
    }

    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;

        return $this;
    }

    protected function bindConfiguration()
    {
        app()->singleton(Configuration::class, function ($app) {
            return new Configuration($this->host, $this->port, $this->auth, $this->basicAuth);
        });
    }

    protected function bindClient()
    {
        app()->singleton('expose.client', function ($app) {
            return $app->make(Client::class);
        });
    }

    protected function bindProxyManager()
    {
        app()->bind(ProxyManager::class, function ($app) {
            return new ProxyManager($app->make(Configuration::class), $this->loop);
        });
    }

    public function createClient()
    {
        $this->bindClient();

        $this->bindConfiguration();

        $this->bindProxyManager();

        return $this;
    }

    public function share($sharedUrl, $subdomain = null, $serverHost = null)
    {
        app('expose.client')->share($sharedUrl, $subdomain, $serverHost);

        return $this;
    }

    public function sharePort(int $port)
    {
        app('expose.client')->sharePort($port);

        return $this;
    }

    protected function addRoutes()
    {
        $this->router->get('/', DashboardController::class);

        $this->router->addPublicFilesystem();

        $this->router->get('/api/tunnels', GetTunnelsController::class);
        $this->router->post('/api/tunnel', CreateTunnelController::class);
        $this->router->get('/api/logs', LogController::class);
        $this->router->post('/api/logs', PushLogsToDashboardController::class);
        $this->router->get('/api/replay/{log}', ReplayLogController::class);
        $this->router->get('/api/logs/clear', ClearLogsController::class);

        $this->app->route('/socket', new WsServer(new Socket()), ['*'], '');

        foreach ($this->router->getRoutes()->all() as $name => $route) {
            $this->app->routes->add($name, $route);
        }
    }

    protected function detectNextAvailablePort($startPort = 4040): int
    {
        while (is_resource(@fsockopen('127.0.0.1', $startPort))) {
            $startPort++;
        }

        return $startPort;
    }

    public function createHttpServer()
    {
        $dashboardPort = $this->detectNextAvailablePort();

        config()->set('expose.dashboard_port', $dashboardPort);

        $this->app = new App('0.0.0.0', $dashboardPort, '0.0.0.0', $this->loop);

        $this->addRoutes();

        return $this;
    }

    public function getApp(): App
    {
        return $this->app;
    }

    public function run()
    {
        $this->loop->run();
    }
}
