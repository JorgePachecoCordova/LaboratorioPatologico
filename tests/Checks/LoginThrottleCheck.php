<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['cache.default' => 'array']);

$route = app('router')->getRoutes()->getByName('login.post');
if (!in_array('throttle:6,1', $route->gatherMiddleware(), true)) {
    throw new RuntimeException('El acceso no limita intentos.');
}
$request = Illuminate\Http\Request::create('/login', 'POST', [], [], [], ['REMOTE_ADDR' => '192.0.2.123']);
$request->setRouteResolver(fn () => $route);
$middleware = app(Illuminate\Routing\Middleware\ThrottleRequests::class);
for ($attempt = 1; $attempt <= 7; $attempt++) {
    try {
        $middleware->handle($request, fn () => response('ok'), 6, 1);
        if ($attempt === 7) throw new RuntimeException('Se permitió el séptimo intento.');
    } catch (Illuminate\Http\Exceptions\ThrottleRequestsException $exception) {
        if ($attempt !== 7 || $exception->getStatusCode() !== 429) throw $exception;
    }
}
echo "PASS: login limitado a seis intentos por minuto y dirección IP.\n";
