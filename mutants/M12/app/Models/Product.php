<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    /**
     * Client-writable attributes only. `hidden` is controlled by the hide/unhide actions and by
     * the company deactivation cascade, never by a submitted form field.
     */
    protected $fillable = [
        'company_id',
        'gtin',
        'name_en',
        'name_fr',
        'description_en',
        'description_fr',
        'brand',
        'country_of_origin',
        'weight_gross',
        'weight_net',
        'weight_unit',
    ];

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
            'weight_gross' => 'float',
            'weight_net' => 'float',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getRouteKeyName(): string
    {
        return 'gtin';
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /** Keyword search over the English and French name and description. */
    public function scopeMatching(Builder $query, ?string $keyword): Builder
    {
        if ($keyword === null || trim($keyword) === '') {
            return $query;
        }

        // Escape the LIKE wildcards so that "%" is searched for, not used as a wildcard.
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($keyword));
        $like = '%'.$escaped.'%';

        return $query->where(function (Builder $inner) use ($like) {
            foreach (['name_en', 'name_fr', 'description_en', 'description_fr'] as $column) {
                $inner->orWhereRaw(
                    sprintf("LOWER(%s) LIKE LOWER(?) ESCAPE '\\'", $column),
                    [$like],
                );
            }
        });
    }

    public function hide(): void
    {
        $this->forceFill(['is_hidden' => true])->save();
    }

    public function unhide(): void
    {
        $this->forceFill(['is_hidden' => false])->save();
    }

    /** A product may only be deleted once it is hidden. */
    public function isDeletable(): bool
    {
        return $this->is_hidden === true;
    }

    /** The product shape used by the JSON API. */
    public function toApiArray(): array
    {
        return [
            'name' => [
                'en' => $this->name_en,
                'fr' => $this->name_fr,
            ],
            'description' => [
                'en' => $this->description_en,
                'fr' => $this->description_fr,
            ],
            'gtin' => $this->gtin,
            'brand' => $this->brand,
            'countryOfOrigin' => $this->country_of_origin,
            'weight' => [
                'gross' => $this->weight_gross,
                'net' => $this->weight_net,
            ],
            'company' => $this->company?->toApiArray(),
        ];
    }
}
