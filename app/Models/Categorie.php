<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
    ];


    public function books(){
        return $this->hasMany(Books::class);
    }
}
