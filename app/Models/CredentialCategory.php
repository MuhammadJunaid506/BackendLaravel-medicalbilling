<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CredentialSubCategory;
class CredentialCategory extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "providercredential_category";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "category_name",
        "created_at",
        "updated_at"
    ];
    public function subcategories()
    {
        return $this->hasMany(CredentialSubCategory::class, 'category_id', 'id');
    }
}
