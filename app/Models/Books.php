<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Books extends Model
{
    protected $table = 'books';

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'author',
        'description',
        'views',
        'total_quantity',
        'degraded_quantity'
    ];




    public function categories(){
        return $this->belongsTo(Categorie::class, 'category_id');
    }
}
