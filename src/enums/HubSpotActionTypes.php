<?php

namespace batchnz\hubspotecommercebridge\enums;


/**
 * Class HubSpotTypes
 * @package batchnz\hubspotecommercebridge\enums
 *
 * Defines the allowed HubSpot data types
 */
abstract class HubSpotActionTypes
{
    public const UPSERT = "UPSERT";
    public const DELETE = "DELETE";
}
