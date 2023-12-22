<?php

namespace App;

class Image extends UuidModel
{
    protected $fillable = [
        'type', 'size', 'suffix'
    ];


    protected $hidden = [
        'pivot'
    ];

}
