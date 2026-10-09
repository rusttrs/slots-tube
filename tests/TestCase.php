<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Uri;

abstract class TestCase extends BaseTestCase
{
    /**
     * Laravel срезает слэш в конце адреса запроса, а у страниц сайта он канонический (CanonicalUrl).
     */
    protected function prepareUrlForRequest($uri)
    {
        $raw = $uri instanceof Uri ? $uri->value() : (string) $uri;
        $url = parent::prepareUrlForRequest($uri);

        [$rawPath] = explode('?', $raw, 2);
        if ($rawPath === '/' || ! str_ends_with($rawPath, '/')) {
            return $url;
        }

        [$path, $query] = array_pad(explode('?', $url, 2), 2, null);

        return rtrim($path, '/').'/'.($query !== null ? '?'.$query : '');
    }
}
