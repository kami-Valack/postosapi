<?php

namespace App\Support;

class GeoDistance
{
    /**
     * Distância em metros entre dois pontos (fórmula de Haversine).
     */
    public static function metersBetween(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): int {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($earthRadius * $c);
    }
}
