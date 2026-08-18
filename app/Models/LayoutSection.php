<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LayoutSection extends Model
{
    protected $guarded = ['id'];
    public function sectionLayoutNews() {
        return $this->hasMany(LayoutSectionNews::class, 'layout_section_id', 'id');
    }
}
