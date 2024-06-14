<?php

declare(strict_types=1);

/**
 * Contains the LinkGroup class.
 *
 * @copyright   Copyright (c) 2022 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2022-02-16
 *
 */

namespace Vanilo\Links\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Vanilo\Links\Contracts\LinkGroup as LinkGroupContract;

/**
 *
 * @property int $id
 * @property int $link_type_id
 * @property int|null $property_id
 * @property int|null $root_item_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read LinkType $type
 * @property-read Collection $items
 * @property-read LinkGroupItem $rootItem
 *
 * @method static LinkGroup create(array $attributes)
 */
class LinkGroup extends Model implements LinkGroupContract
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'property_id' => 'integer',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(LinkTypeProxy::modelClass(), 'link_type_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LinkGroupItemProxy::modelClass(), 'link_group_id', 'id');
    }

    public function rootItem(): BelongsTo
    {
        return $this->belongsTo(LinkGroupItemProxy::modelClass(), 'root_item_id', 'id');
    }

    public function isUnidirectional(): bool
    {
        return null !== $this->root_item_id;
    }

    public function isOmnidirectional(): bool
    {
        return null === $this->root_item_id;
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }
}
