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
    public const CONTACT = "CONTACT"; //Customer (Craft Equivalent)
    public const DEAL = "DEAL"; //Order (Craft Equivalent)
    public const PRODUCT = "PRODUCT"; //Variant (Craft Equivalent)
    public const LINE_ITEM = "LINE_ITEM"; //LineItem (Craft Equivalent)
}
