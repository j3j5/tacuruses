<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Snowflake Epoch
    |--------------------------------------------------------------------------
    |
    | Set the date the application was develop started. Don't set a date in
    | the future.
    |
    */
    'epoch' => env('SNOWFLAKE_EPOCH', '2022-12-31 23:59:59'),

    /*
    |--------------------------------------------------------------------------
    | Snowflake Configuration
    |--------------------------------------------------------------------------
    |
    | Due to the size in bits kept for datacenter ID and worker ID on the snowflake
    | implementation, values must be between 0 and 31
    |
    */
    'datacenter_id' => env('SNOWFLAKE_DATACENTER_ID', 1),

    'worker_id' => env('SNOWFLAKE_WORKER_ID', 1),
];