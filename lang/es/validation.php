<?php

$messages = json_decode(file_get_contents(__DIR__.'/messages.json'), true, 512, JSON_THROW_ON_ERROR);
unset($messages['password']);

return array_replace_recursive(\Illuminate\Support\Arr::undot($messages), [
    'attributes' => require __DIR__.'/attributes.php',
]);
