<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Valores de Comisiones
    |--------------------------------------------------------------------------
    */

    // CAMBIO: De 30.00 a 20.00 para cumplir con "el resto 20"
    'portability_extra' => 20.00, 

    /*
    |--------------------------------------------------------------------------
    | Excepciones de Portabilidad
    |--------------------------------------------------------------------------
    */
    'portability_group_exceptions' => [
        'Grupo+Orange',
        'Orange',
        'Yoigo',
        'Pepephone',
        'Llamaya',
        'Lebara',
        // ...
    ],

];