<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums;

use GoDaddy\WordPress\MWC\Core\Traits\EnumTrait;

/**
 * Enum-like class for defining Commerce table column names.
 */
class CommerceTableColumns
{
    use EnumTrait;

    public const Id = 'id';
    public const CommerceContextId = 'commerce_context_id';
    public const CommerceId = 'commerce_id';
    public const LocalId = 'local_id';
    public const Name = 'name';
    public const ResourceTypeId = 'resource_type_id';
    public const RemoteUpdatedAt = 'remote_updated_at';
}
