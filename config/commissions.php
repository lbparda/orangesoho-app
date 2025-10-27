<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Valores de Comisiones
    |--------------------------------------------------------------------------
    |
    | Aquí se definen las comisiones globales para la aplicación.
    |
    */

    'portability_extra' => 30.00,

    /*
    |--------------------------------------------------------------------------
    | Excepciones de Portabilidad
    |--------------------------------------------------------------------------
    |
    | Operadores (source_operator) que NO suman la comisión extra
    | por portabilidad (ej. por ser del mismo grupo).
    |
    */
    'portability_group_exceptions' => [
        'Grupo+Orange',
        'Orange',
        'Yoigo',
        'Pepephone',
        'Llamaya',
        'Lebara',
        // Añade cualquier otro operador del grupo que uses
    ],

];