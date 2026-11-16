<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
view()->share('errors', new Illuminate\Support\ViewErrorBag());

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash};
use Illuminate\Validation\ValidationException;

$user = User::create(['name' => 'Usuario prueba', 'username' => 'perfil_prueba', 'password' => 'Original123', 'role' => 'reception', 'is_active' => true]);
$other = User::create(['name' => 'Otra cuenta', 'username' => 'otro_usuario', 'email' => 'otro@example.com', 'password' => 'Original456', 'role' => 'administrator', 'is_active' => true]);
Auth::login($user);
$controller = app(App\Http\Controllers\PerfilController::class);
$requestFor = function (array $data) use ($user): Request {
    $request = Request::create('/mi-perfil', 'PATCH', $data);
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession(app('session')->driver());
    return $request;
};
$data = ['name' => 'Nombre actualizado', 'username' => 'nuevo_usuario', 'email' => 'perfil@example.com', 'phone' => '2622600', 'mobile' => '70000000', 'address' => 'Potosí', 'role' => 'administrator', 'is_active' => false, 'id' => $other->id];
$controller->update($requestFor($data));
$user->refresh();
if ($user->username !== 'nuevo_usuario' || $user->mobile !== '70000000' || $user->address !== 'Potosí' || $user->role !== 'reception' || !$user->is_active || $other->fresh()->name !== 'Otra cuenta') throw new RuntimeException('Profile update or isolation failed');
foreach (['username' => 'otro_usuario', 'email' => 'otro@example.com'] as $field => $value) {
    try { $controller->update($requestFor(array_replace($data, [$field => $value]))); throw new RuntimeException('Duplicate accepted'); }
    catch (ValidationException $e) { if (!isset($e->errors()[$field])) throw $e; }
}
foreach ([['current_password' => 'incorrecta', 'password' => 'Nueva_12345', 'password_confirmation' => 'Nueva_12345'], ['current_password' => 'Original123', 'password' => 'Nueva_12345', 'password_confirmation' => 'distinta']] as $payload) {
    try { $controller->password($requestFor($payload)); throw new RuntimeException('Invalid password change accepted'); }
    catch (ValidationException $e) { }
}
foreach (['minuscula_123', 'Mayuscula123', 'Mayuscula 123', 'A_1'] as $weak) {
    try { $controller->password($requestFor(['current_password' => 'Original123', 'password' => $weak, 'password_confirmation' => $weak])); throw new RuntimeException('Weak password accepted'); }
    catch (ValidationException $e) { if (!isset($e->errors()['password'])) throw $e; }
    $admin = app(App\Http\Controllers\ConfiguracionController::class);
    $payload = ['name' => 'Prueba', 'username' => 'prueba_politica', 'role' => 'reception', 'password' => $weak, 'password_confirmation' => $weak];
    foreach (['storeUsuario', 'updateUsuario'] as $method) {
        try {
            if ($method === 'storeUsuario') $admin->$method($requestFor($payload));
            else $admin->$method($requestFor($payload), $other);
            throw new RuntimeException('Weak admin password accepted');
        } catch (ValidationException $e) { if (!isset($e->errors()['password'])) throw $e; }
    }
}
foreach (['Mayuscula.123', 'Mayuscula_123'] as $valid) {
    if (validator(['password' => $valid], ['password' => ['min:8', new App\Rules\PasswordCharacters()]])->fails()) throw new RuntimeException('Valid password rejected');
}
if (!Hash::check('Original123', $user->fresh()->password)) throw new RuntimeException('Invalid change altered password');
$controller->password($requestFor(['current_password' => 'Original123', 'password' => 'Nueva_12345', 'password_confirmation' => 'Nueva_12345']));
if (!Hash::check('Nueva_12345', $user->fresh()->password)) throw new RuntimeException('Password was not saved securely');
$html = $controller->edit($requestFor([]))->render();
foreach (['Mi perfil', 'Nombre actualizado', 'Cambiar contraseña', 'perfil.update', 'perfil.password'] as $text) {
    if (str_starts_with($text, 'perfil.')) $text = route($text);
    if (!str_contains($html, $text)) throw new RuntimeException('Missing profile content '.$text);
}
foreach (['perfil.edit', 'perfil.update', 'perfil.password'] as $name) {
    if (!in_array('patologia.auth', app('router')->getRoutes()->getByName($name)->gatherMiddleware(), true)) throw new RuntimeException('Profile route not protected');
}
echo "PASS: datos personales, aislamiento de cuentas y roles, duplicados, contraseña actual, confirmación, hash, vista y rutas protegidas.\n";
