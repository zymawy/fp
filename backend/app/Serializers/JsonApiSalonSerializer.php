<?php

namespace App\Serializers;

class JsonApiSalonSerializer extends \PHPOpenSourceSaver\Fractal\Serializer\JsonApiSerializer
{
    /**
     * {@inheritDoc}
     */
    public function collection(?string $resourceKey, array $data): array
    {
        $resources = [];

        foreach ($data as $resource) {
            $resources[] = $this->item($resourceKey, $resource)['data'];
        }

        return ['data' => $resources];
    }
//
//    /**
//     * {@inheritDoc}
//     */
//    public function item(?string $resourceKey, array $data): array
//    {
//        return ['success' => true, 'data' => $data];
//    }
//
//    /**
//     * {@inheritDoc}
//     */
//    public function null(): ?array
//    {
//        return ['success' => true, 'data' => []];
//    }
}