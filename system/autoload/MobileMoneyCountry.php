<?php

/**
 * Pays supportés pour Mobile Money (passerelle + fuseau horaire).
 */
class MobileMoneyCountry
{
    public static function catalog()
    {
        return [
            'GA' => [
                'code' => 'GA',
                'name' => 'Gabon',
                'timezone' => 'Africa/Libreville',
                'phone_prefix' => '241',
                'gateway' => 'mypvit',
                'available' => true,
                'payment_label' => 'MyPVit (Airtel / Moov)',
            ],
            'CM' => [
                'code' => 'CM',
                'name' => 'Cameroun',
                'timezone' => 'Africa/Douala',
                'phone_prefix' => '237',
                'gateway' => 'campay',
                'available' => true,
                'payment_label' => 'CamPay (MTN / Orange)',
            ],
        ];
    }

    public static function resolve($code)
    {
        $code = strtoupper(trim((string) $code));
        $catalog = self::catalog();
        return $catalog[$code] ?? null;
    }

    /** @return array<int, array> */
    public static function availableForProvisioning()
    {
        return array_values(array_filter(self::catalog(), static function ($country) {
            return !empty($country['available']);
        }));
    }

    /**
     * @return array{ok: bool, message?: string, country?: array}
     */
    public static function validateForProvision($code)
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return ['ok' => false, 'message' => Lang::T('Please select your country')];
        }

        $country = self::resolve($code);
        if (!$country) {
            return ['ok' => false, 'message' => Lang::T('The selected country is not supported')];
        }
        if (empty($country['available'])) {
            return [
                'ok' => false,
                'message' => Lang::T('Mobile Money payment is not available for this country yet. Please choose Gabon or Cameroon.'),
            ];
        }
        if (!in_array($country['gateway'], MobileMoneyGateway::MOBILE_GATEWAYS, true)) {
            return ['ok' => false, 'message' => Lang::T('Payment gateway not configured for this country')];
        }

        return ['ok' => true, 'country' => $country];
    }

    public static function defaultCountryCode()
    {
        return 'GA';
    }
}
