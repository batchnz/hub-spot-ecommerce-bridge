<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\models\HubspotModel;

interface HubspotServiceInterface
{
    public function fetch(int $id): HubspotModel;
    public function mapProperties($model): array;
    public function findInHubspot($model): string|false;
    public function upsertToHubspot($model): string|false;
    public function deleteFromHubspot($model): int|false;
}
