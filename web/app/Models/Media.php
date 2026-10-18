<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $guarded = ['id'];

    /** Files live in public/media (see config/filesystems.php); asset() follows the host the page was served on. */
    public function url(): string
    {
        return asset('media/'.$this->path);
    }
}
