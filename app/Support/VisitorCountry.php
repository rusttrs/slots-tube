<?php

namespace App\Support;

use Illuminate\Http\Request;

class VisitorCountry
{
    public static function code(?Request $request = null): string
    {
        $request ??= request();
        $all = (string) config('bonus_countries.all_code', 'ALL');

        // Удобно для проверки на staging без Cloudflare.
        if (! app()->isProduction()) {
            $override = strtoupper((string) $request->query('country', ''));
            if ($override !== '' && preg_match('/^[A-Z]{2}$/', $override)) {
                return $override;
            }
            if ($override === $all) {
                return $all;
            }
        }

        $cf = strtoupper(trim((string) $request->header('CF-IPCountry', '')));
        if ($cf === '' || in_array($cf, ['XX', 'T1'], true)) {
            return strtoupper((string) config('bonus_countries.unknown_as', $all));
        }

        if (! preg_match('/^[A-Z]{2}$/', $cf)) {
            return strtoupper((string) config('bonus_countries.unknown_as', $all));
        }

        return $cf;
    }
}
