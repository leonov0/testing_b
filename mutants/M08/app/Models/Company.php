<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'company_name',
        'company_address',
        'company_telephone',
        'company_email',
        'owner_name',
        'owner_mobile',
        'owner_email',
        'contact_name',
        'contact_mobile',
        'contact_email',
    ];

    protected function casts(): array
    {
        return ['deactivated' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Deactivating a company hides every product it owns.
     * Reactivating does not unhide them again - each product is unhidden by hand.
     */
    public function deactivate(): void
    {
        $this->forceFill(['deactivated' => true])->save();
    }

    public function reactivate(): void
    {
        $this->forceFill(['deactivated' => false])->save();
    }

    /** The company shape used by the JSON API. */
    public function toApiArray(): array
    {
        return [
            'companyName' => $this->company_name,
            'companyAddress' => $this->company_address,
            'companyTelephone' => $this->company_telephone,
            'companyEmail' => $this->company_email,
            'owner' => [
                'name' => $this->owner_name,
                'mobileNumber' => $this->owner_mobile,
                'email' => $this->owner_email,
            ],
            'contact' => [
                'name' => $this->contact_name,
                'mobileNumber' => $this->contact_mobile,
                'email' => $this->contact_email,
            ],
        ];
    }
}
