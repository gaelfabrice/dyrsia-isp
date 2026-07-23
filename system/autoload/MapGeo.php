<?php

/**
 * Helpers for admin map pages (customers, routers, ODPs).
 */
class MapGeo
{
    /** Default center: Douala, Cameroon */
    const DEFAULT_LAT = 4.0511;
    const DEFAULT_LNG = 9.7679;
    const DEFAULT_ZOOM = 6;

    public static function parseCoordinates($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $raw = trim($raw, "[]() \t\n\r\0\x0B");
        if (!preg_match('/^(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)$/', $raw, $matches)) {
            return null;
        }

        $lat = (float) $matches[1];
        $lng = (float) $matches[2];
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    public static function defaultCenter()
    {
        return [
            'lat' => self::DEFAULT_LAT,
            'lng' => self::DEFAULT_LNG,
            'zoom' => self::DEFAULT_ZOOM,
        ];
    }

    public static function scopedCustomersQuery($admin)
    {
        $query = ORM::for_table('tbl_customers');
        if (($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $query->where('created_by', (int) ($admin['id'] ?? 0));
        }

        return $query;
    }

    public static function applyCustomerSearch($query, $search)
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $query;
        }

        $like = '%' . $search . '%';
        return $query->where_raw(
            '(fullname LIKE ? OR username LIKE ? OR email LIKE ? OR phonenumber LIKE ? OR address LIKE ?)',
            [$like, $like, $like, $like, $like]
        );
    }

    public static function buildCustomerMapData($admin, $search = '')
    {
        $query = self::scopedCustomersQuery($admin);
        self::applyCustomerSearch($query, $search);
        $customers = $query->order_by_asc('fullname')->find_many();

        $routerCoords = self::routerCoordinatesByName($admin);
        $points = [];
        $withoutCoords = [];

        foreach ($customers as $customer) {
            $coords = self::parseCoordinates($customer->coordinates);
            $approximate = false;
            $locationSource = 'customer';

            if (!$coords) {
                $routerName = self::activeRouterNameForCustomer((int) $customer->id);
                if ($routerName !== '' && isset($routerCoords[$routerName])) {
                    $coords = $routerCoords[$routerName];
                    $approximate = true;
                    $locationSource = 'router';
                }
            }

            if (!$coords) {
                $withoutCoords[] = [
                    'id' => (int) $customer->id,
                    'name' => (string) $customer->fullname,
                    'username' => (string) $customer->username,
                    'address' => (string) $customer->address,
                    'status' => (string) $customer->status,
                ];
                continue;
            }

            $points[] = [
                'id' => (int) $customer->id,
                'name' => (string) $customer->fullname,
                'username' => (string) $customer->username,
                'balance' => (string) $customer->balance,
                'address' => (string) $customer->address,
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'approximate' => $approximate,
                'location_source' => $locationSource,
                'service_type' => (string) $customer->service_type,
                'email' => (string) $customer->email,
                'phonenumber' => (string) $customer->phonenumber,
                'status' => (string) $customer->status,
            ];
        }

        return [
            'points' => $points,
            'without_coords' => $withoutCoords,
            'center' => self::centerFromPoints($points),
            'stats' => [
                'total' => count($customers),
                'mapped' => count($points),
                'missing' => count($withoutCoords),
            ],
        ];
    }

    public static function centerFromPoints(array $points)
    {
        if ($points === []) {
            return self::defaultCenter();
        }

        $lats = array_column($points, 'lat');
        $lngs = array_column($points, 'lng');

        return [
            'lat' => array_sum($lats) / count($lats),
            'lng' => array_sum($lngs) / count($lngs),
            'zoom' => count($points) === 1 ? 14 : null,
        ];
    }

    private static function routerCoordinatesByName($admin)
    {
        $query = ORM::for_table('tbl_routers')->where_not_equal('coordinates', '');
        if (($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $query->where('admin_id', (int) ($admin['id'] ?? 0));
        }

        $map = [];
        foreach ($query->find_many() as $router) {
            $coords = self::parseCoordinates($router->coordinates);
            if ($coords) {
                $map[(string) $router->name] = $coords;
            }
        }

        return $map;
    }

    private static function activeRouterNameForCustomer($customerId)
    {
        $recharge = ORM::for_table('tbl_user_recharges')
            ->where('customer_id', $customerId)
            ->where('status', 'on')
            ->order_by_desc('id')
            ->find_one();

        return $recharge ? trim((string) $recharge->routers) : '';
    }
}
