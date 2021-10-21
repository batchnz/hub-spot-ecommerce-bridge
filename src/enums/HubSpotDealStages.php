<?php

namespace batchnz\hubspotecommercebridge\enums;


/**
 * Class HubSpotDealStages
 * @package batchnz\hubspotecommercebridge\enums
 *
 * Defines the allowed HubSpot deal stages
 */
abstract class HubSpotDealStages
{
    public const ABANDONED = "checkout_abandoned";
    public const PENDING = "checkout_pending";
    public const COMPLETED = "checkout_completed";
    public const PROCESSED = "processed";
    public const SHIPPED = "shipped";
    public const CANCELLED = "cancelled";

    //TODO allow the user to define the deal pipeline with their custom order statuses
    public const PIPELINE = [
        "new" => self::PENDING,
        "processed" => self::PROCESSED,
        "pending" => self::ABANDONED,
        "operator1" => self::PROCESSED,
        "operator2" => self::PROCESSED,
        "operator3" => self::PROCESSED,
    ];
}
