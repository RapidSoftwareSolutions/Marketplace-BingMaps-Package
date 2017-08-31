<?php
$routes = [
    'metadata',
    'findLocationByAddress',
    'findLocationByPoint',
    'findLocationByQuery',
    'getElevationValues',
    'getStaticMap',
    'getImageryMetadata',
    'calculateRoute',
    'calculateRouteFromMajorRoads',
    'getTrafficIncidents'
];
foreach($routes as $file) {
    require __DIR__ . '/../src/routes/'.$file.'.php';
}

