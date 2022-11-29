<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\models\HubspotModel;

interface HubspotServiceInterface
{
    public function fetch(int $id): HubspotModel;
    public function mapProperties($model): array;
    public function upsertToHubspot($model): int|false;
    public function deleteFromHubspot($model): int|false;
}
