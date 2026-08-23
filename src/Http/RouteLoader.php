<?php

namespace App\Http;

use App\Http\Routes\AdoptionListingRoutes;
use App\Http\Routes\AdoptionRoutes;
use App\Http\Routes\AnalyticsRoutes;
use App\Http\Routes\AnimalRoutes;
use App\Http\Routes\AuthRoutes;
use App\Http\Routes\CaseRoutes;
use App\Http\Routes\ElearningRoutes;
use App\Http\Routes\GeoRoutes;
use App\Http\Routes\MessageRoutes;
use App\Http\Routes\NotificationRoutes;
use App\Http\Routes\ReportRoutes;
use App\Http\Routes\UserRoutes;

class RouteLoader
{
    public static function register(Router $router, array $d): void
    {
        AuthRoutes::register($router, $d);
        UserRoutes::register($router, $d);
        ReportRoutes::register($router, $d);
        CaseRoutes::register($router, $d);
        AnimalRoutes::register($router, $d);
        AdoptionRoutes::register($router, $d);
        AdoptionListingRoutes::register($router, $d);
        MessageRoutes::register($router, $d);
        NotificationRoutes::register($router, $d);
        ElearningRoutes::register($router, $d);
        AnalyticsRoutes::register($router, $d);
        GeoRoutes::register($router, $d);
    }
}
