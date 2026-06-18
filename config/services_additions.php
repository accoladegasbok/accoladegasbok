<?php
/**
 * Auto Zenith — services.php additions
 * Add these keys to your existing config/services.php file.
 * Add the corresponding values to your .env file.
 *
 * .env keys to add:
 * --------------------------------------------------
 * CARAPI_KEY=your_carapi_api_token_here
 * CARAPI_SECRET=your_carapi_api_secret_here
 * WHATSAPP_US=15125873425
 * WHATSAPP_NG=2347064413764
 * WHATSAPP_GH=2349155688804
 * --------------------------------------------------
 */

return [

    /*
    |--------------------------------------------------------------------------
    | CarAPI.app — Vehicle Catalog ($199/yr = $16.58/mo)
    | Get key at: https://carapi.app/register
    |--------------------------------------------------------------------------
    */
    'carapi' => [
        'key'    => env('CARAPI_KEY'),
        'secret' => env('CARAPI_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Numbers by Region
    |--------------------------------------------------------------------------
    */
    'whatsapp' => [
        'us' => env('WHATSAPP_US', '15125873425'),
        'ng' => env('WHATSAPP_NG', '2347064413764'),
        'gh' => env('WHATSAPP_GH', '2349155688804'),
    ],

    /*
    |--------------------------------------------------------------------------
    | NHTSA vPIC — FREE, no key needed
    | Endpoint: https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVin/{VIN}?format=json
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ExchangeRate-API — FREE tier (1,500 calls/mo)
    | Register at: https://www.exchangerate-api.com (no key needed for open tier)
    |--------------------------------------------------------------------------
    */

];
