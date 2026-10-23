<?php

namespace App\Integrations\Support;

/**
 * Reference directory of well-known Indian city coordinates plus geodesic helpers.
 *
 * This is *public geographic reference data* used only to produce openly labelled
 * distance estimates. It is deliberately small and explicit: if a city is not in the
 * directory the application says so instead of guessing coordinates.
 */
final class CityDirectory
{
    /** @var array<string,array{0:float,1:float}> */
    public const CITIES = [
        'Delhi' => [28.6139, 77.2090], 'New Delhi' => [28.6139, 77.2090], 'Mumbai' => [19.0760, 72.8777],
        'Kolkata' => [22.5726, 88.3639], 'Chennai' => [13.0827, 80.2707], 'Bengaluru' => [12.9716, 77.5946],
        'Hyderabad' => [17.3850, 78.4867], 'Pune' => [18.5204, 73.8567], 'Ahmedabad' => [23.0225, 72.5714],
        'Jaipur' => [26.9124, 75.7873], 'Lucknow' => [26.8467, 80.9462], 'Kanpur' => [26.4499, 80.3319],
        'Patna' => [25.5941, 85.1376], 'Bhopal' => [23.2599, 77.4126], 'Indore' => [22.7196, 75.8577],
        'Nagpur' => [21.1458, 79.0882], 'Surat' => [21.1702, 72.8311], 'Vadodara' => [22.3072, 73.1812],
        'Coimbatore' => [11.0168, 76.9558], 'Madurai' => [9.9252, 78.1198], 'Kochi' => [9.9312, 76.2673],
        'Thiruvananthapuram' => [8.5241, 76.9366], 'Bhubaneswar' => [20.2961, 85.8245], 'Guwahati' => [26.1445, 91.7362],
        'Chandigarh' => [30.7333, 76.7794], 'Dehradun' => [30.3165, 78.0322], 'Agra' => [27.1767, 78.0081],
        'Varanasi' => [25.3176, 82.9739], 'Allahabad' => [25.4358, 81.8463], 'Prayagraj' => [25.4358, 81.8463],
        'Ranchi' => [23.3441, 85.3096], 'Raipur' => [21.2514, 81.6296], 'Visakhapatnam' => [17.6868, 83.2185],
        'Vijayawada' => [16.5062, 80.6480], 'Mysuru' => [12.2958, 76.6394], 'Kota' => [25.2138, 75.8648],
        'Amritsar' => [31.6340, 74.8723], 'Jammu' => [34.0837, 74.7973], 'Shimla' => [31.1048, 77.1734],
        'Udaipur' => [24.5854, 73.7125], 'Jodhpur' => [26.2389, 73.0243], 'Gwalior' => [26.2183, 78.1828],
        'Meerut' => [28.9845, 77.7064], 'Gorakhpur' => [26.7606, 83.3732], 'Siliguri' => [26.7271, 88.3953],
        'Imphal' => [24.8170, 93.9368], 'Aizawl' => [23.7271, 92.7176], 'Port Blair' => [11.6234, 92.7265],
        'Noida' => [28.5355, 77.3910], 'Gurugram' => [28.4595, 77.0266], 'Faridabad' => [28.4089, 77.3178],
        'Ghaziabad' => [28.6692, 77.4538], 'Nashik' => [19.9975, 73.7898], 'Thane' => [19.2183, 72.9781],
        'Aurangabad' => [19.8762, 75.3433], 'Jabalpur' => [23.1815, 79.9864], 'Ujjain' => [23.1793, 75.7849],
        'Bareilly' => [28.3670, 79.4304], 'Aligarh' => [27.8974, 78.0880], 'Moradabad' => [28.8386, 78.7733],
        'Jalandhar' => [31.3260, 75.5762], 'Ludhiana' => [30.9010, 75.8573], 'Rohtak' => [28.8955, 76.6066],
        'Hisar' => [29.1492, 75.7217], 'Bikaner' => [28.0229, 73.3119], 'Ajmer' => [26.4499, 74.6399],
        'Trichy' => [10.7905, 78.7047], 'Salem' => [11.6643, 78.1460], 'Tirupati' => [13.6288, 79.4192],
        'Warangal' => [17.9689, 79.5941], 'Guntur' => [16.3067, 80.4365], 'Nellore' => [14.4426, 79.9865],
        'Kozhikode' => [11.2588, 75.7804], 'Thrissur' => [10.5276, 76.2144], 'Kollam' => [8.8932, 76.6141],
        'Cuttack' => [20.4625, 85.8828], 'Rourkela' => [22.2604, 84.8536], 'Dhanbad' => [23.7957, 86.4304],
        'Jamshedpur' => [22.8046, 86.2029], 'Bokaro' => [23.6693, 86.1511], 'Muzaffarpur' => [26.1209, 85.3647],
        'Gaya' => [24.7955, 85.0002], 'Bhagalpur' => [25.2425, 86.9842], 'Darbhanga' => [26.1542, 85.8918],
        'Agra' => [27.1767, 78.0081], 'Mathura' => [27.4924, 77.6737], 'Jhansi' => [25.4484, 78.5685],
        'Nanded' => [19.1383, 77.3210], 'Kolhapur' => [16.7050, 74.2433], 'Solapur' => [17.6599, 75.9064],
        'Rajkot' => [22.3039, 70.8022], 'Bhavnagar' => [21.7645, 72.1519], 'Jamnagar' => [22.4707, 70.0577],
    ];

    /** @return array<int,string> */
    public static function names(): array
    {
        return array_keys(self::CITIES);
    }

    /** Case-insensitive, whitespace-tolerant lookup. */
    public static function coords(?string $city): ?array
    {
        if ($city === null || trim($city) === '') {
            return null;
        }

        $needle = self::normalise($city);

        foreach (self::CITIES as $name => $coords) {
            if (self::normalise($name) === $needle) {
                return $coords;
            }
        }

        // Tolerate common suffixes such as "New Delhi (NCR)" or "Delhi, India".
        foreach (self::CITIES as $name => $coords) {
            if (str_starts_with($needle, self::normalise($name))) {
                return $coords;
            }
        }

        return null;
    }

    public static function normalise(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    /** Great-circle distance in kilometres (haversine, spherical earth). */
    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * asin(sqrt($a));
    }

    /**
     * Approximate road distance from a great-circle distance.
     * The 1.3 detour factor is a disclosed planning assumption, not a measurement.
     */
    public static function roadKmApprox(float $straightKm, float $factor = 1.3): int
    {
        return max(1, (int) round($straightKm * $factor));
    }
}
