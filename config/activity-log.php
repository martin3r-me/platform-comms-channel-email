<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Eloquent-Events, die getrackt werden sollen
    |--------------------------------------------------------------------------
    |
    | Hier listet ihr die Laravel-Events auf, z.B.:
    */
    'events' => [
        'created',
        'updated',
        'deleted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribute, die beim Tracken ignoriert werden sollen
    |--------------------------------------------------------------------------
    |
    | Diese Felder werden aus dem `properties`-Array herausgefiltert.
    */
    'ignore_attributes' => [
        'created_at',
        'updated_at',
    ],

];
