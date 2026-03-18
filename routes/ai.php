<?php

use App\Mcp\Servers\LifeOsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', LifeOsServer::class)
    ->middleware(['auth:sanctum']);
