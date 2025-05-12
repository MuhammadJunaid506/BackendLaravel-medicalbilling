<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CredentialCategory;
class CredentialSubCategory extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "providercredential_subcategory";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "category_id",
        "sub_category_name",
        "created_at",
        "updated_at"
    ];
    public function category()
    {
        return $this->belongsTo(CredentialCategory::class, 'category_id', 'id');
    }
}
