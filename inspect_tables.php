<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = ['shopify_orders', 'shopify_order_products'];
$output = "";

foreach ($tables as $table) {
    $output .= "TABLE: $table\n";
    $columns = Illuminate\Support\Facades\DB::select("DESCRIBE $table");
    foreach ($columns as $col) {
        $output .= " - " . $col->Field . " (" . $col->Type . ")\n";
    }
    $output .= "\n";
}

file_put_contents('schema_output.txt', $output);
