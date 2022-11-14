<?php

namespace App\Services\ConfigurationProvider;

class ConfigurationProvider
{
    public static function getConfig()
    {
        return [
            [
                "projectId" => "saaslowprices",
                "protected-by" => [
                    'token' => [
                        'token' => 'teH1IPdu6h2y',
                        'interval' => 100
                    ]
                ]
            ],
        ];
    }

    public static function getJson() {
        $json = storage_path('/app');
        $json .='/saaslowprices-22c63e1a3961.json';
        return $json;
    }
}
