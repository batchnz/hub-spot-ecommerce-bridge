<?php

namespace batchnz\hubspotecommercebridge\enums;


/**
 * Class HubSpotTypes
 * @package batchnz\hubspotecommercebridge\enums
 *
 * Defines the allowed HubSpot data types
 */
abstract class HubSpotObjectTypes
{
    public const CONTACT = "CONTACT";
    public const DEAL = "DEAL";
    public const PRODUCT = "PRODUCT";
    public const LINE_ITEM = "LINE_ITEM";
}
