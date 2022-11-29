<?php

namespace batchnz\hubspotecommercebridge\enums;

/**
 * Class HubSpotTypes
 * @package batchnz\hubspotecommercebridge\enums
 *
 * Defines the allowed HubSpot association IDs
 * @see https://legacydocs.hubspot.com/docs/methods/crm-associations/crm-associations-overview
 */
abstract class HubSpotAssocitations
{
    public const LINE_ITEM_TO_DEAL = 20;
    public const CONTACT_TO_DEAL = 4;
}
