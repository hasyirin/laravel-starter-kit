<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\User\Models\User;
use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveUserPreferences
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $timezone = $this->browserTimezone($request);
        $locale = $this->browserLocale($request);
        $user = auth()->user();

        if ($user instanceof User) {
            $missing = array_filter([
                'timezone' => $user->timezone === null ? $timezone : null,
                'locale' => $user->locale === null ? $locale : null,
            ]);

            if ($missing !== []) {
                $user->forceFill($missing)->saveQuietly();
            }
        }

        context()->addHidden([
            'timezone' => ($user instanceof User ? $user->timezone : null) ?? $timezone ?? config()->string('system.timezone'),
            'locale' => ($user instanceof User ? $user->locale : null) ?? $locale ?? config()->string('system.locale'),
        ]);

        return $next($request);
    }

    /**
     * Chrome reports some zones by their old names, such as `Asia/Calcutta`, which PHP lists as `Asia/Kolkata`.
     * `UTC` stays as it is, because its IANA name, `Etc/UTC`, is not in the list of PHP.
     * PHP has `intltz_get_iana_id()` only with ICU 74 or later. With an older ICU, an old name falls back to the default zone.
     */
    private function browserTimezone(Request $request): ?string
    {
        $timezone = $request->cookie('timezone');

        if (! is_string($timezone)) {
            return null;
        }

        foreach ([function_exists('intltz_get_iana_id') ? intltz_get_iana_id($timezone) : false, $timezone] as $candidate) {
            if (is_string($candidate) && in_array($candidate, DateTimeZone::listIdentifiers(), true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * The cookie lists the browser languages in order. The first one with a supported locale wins,
     * then the first one whose language matches a supported locale, such as `en-SG` for `en-MY`.
     */
    private function browserLocale(Request $request): ?string
    {
        $cookie = $request->cookie('locale');
        $languages = array_map(trim(...), explode(',', strtolower(is_string($cookie) ? $cookie : '')));
        $locales = array_filter(config()->array('system.locales'), is_string(...));

        foreach ([false, true] as $byLanguage) {
            foreach ($languages as $language) {
                foreach ($locales as $locale) {
                    $candidate = strtolower($locale);

                    if ($byLanguage ? explode('-', $candidate)[0] === explode('-', $language)[0] : $candidate === $language) {
                        return $locale;
                    }
                }
            }
        }

        return null;
    }
}
