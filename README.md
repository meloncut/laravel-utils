# Laravel Develop Util
## custom softdelete fields
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Meloncut\LaravelUtils\Database\MelonSoftDeletes;

class Dummy extends Model {
    use MelonSoftDeletes, HasFactory;

    protected $table = 'dummy';

    protected $fillable = ['name'];

    public $timestamps = false;
}
```
