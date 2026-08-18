<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryLayout extends Model
{
    //
    public function layoutNews() {
        return $this->hasMany(CategoryLayoutNews::class, 'category_layout_id', 'id');
    }
}
