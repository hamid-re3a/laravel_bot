<?php
/**
 * Created by PhpStorm.
 * User: saeed
 * Date: 1/20/2019
 * Time: 1:32 PM
 */

namespace App\Reservina\Transformers;


class ServiceProviderTransformer extends Transformer
{

    public function transform($service_provider)
    {
        return [
            'name' => $service_provider['name'],
            'service_type' => $service_provider['service_provider_type_id']
        ];
    }
}