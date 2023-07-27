<?php

namespace batchnz\hubspotecommercebridge\services;

use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\elements\User;

class ManualSyncService extends Component
{
    public const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * Fetches Product Ids within a given date range,
     * If no date range is given, fetches all Product Ids
     *
     * @param \DateTime|false $startDate
     * @param \DateTime|false $endDate
     * @return array
     */
    public function fetchProductIds(\DateTime|false $startDate, \DateTime|false $endDate): array
    {
        $query = Variant::find();
        if ($startDate) {
            $startDateString = $this->formatDate($startDate);
            $query = $query->andWhere("{{%elements.dateUpdated}} >= {$startDateString}");
        }
        if ($endDate) {
            $endDateString = $this->formatDate($endDate);
            $query = $query->andWhere("{{%elements.dateUpdated}} <= {$endDateString}");
        }
        return $query->ids();
    }

    /**
     * Fetches Order Ids within a given date range,
     * If no date range is given, fetches all Order Ids
     *
     * @param \DateTime|false $startDate
     * @param \DateTime|false $endDate
     * @return array
     */
    public function fetchOrderIds(\DateTime|false $startDate, \DateTime|false $endDate): array
    {
        $query = Order::find();
        if ($startDate) {
            $startDateString = $this->formatDate($startDate);
            $query = $query->andWhere("{{%elements.dateUpdated}} >= {$startDateString}");
        }
        if ($endDate) {
            $endDateString = $this->formatDate($endDate);
            $query = $query->andWhere("{{%elements.dateUpdated}} <= {$endDateString}");
        }
        return $query->ids();
    }

    /**
     * Fetches User Ids within a given date range,
     * If no date range is given, fetches all User Ids
     *
     * @param \DateTime|false $startDate
     * @param \DateTime|false $endDate
     * @return array
     */
    public function fetchUserIds(\DateTime|false $startDate, \DateTime|false $endDate): array
    {
        $query = User::find();
        if ($startDate) {
            $startDateString = $this->formatDate($startDate);
            $query = $query->andWhere("{{%elements.dateUpdated}} >= {$startDateString}");
        }
        if ($endDate) {
            $endDateString = $this->formatDate($endDate);
            $query = $query->andWhere("{{%elements.dateUpdated}} <= {$endDateString}");
        }
        return $query->ids();
    }

    /**
     * Formats date in a format that db queries can process
     *
     * @param \DateTime $date
     * @return string
     */
    public function formatDate(\DateTime $date): string
    {
        return "'{$date->format(self::DATE_FORMAT)}'";
    }
}
