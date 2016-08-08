<?php

namespace TypiCMS\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use TypiCMS\Modules\History\Shells\Traits\Historable;

abstract class BaseTranslation extends Model
{
    use Historable;

    protected $touches = ['owner'];
}
