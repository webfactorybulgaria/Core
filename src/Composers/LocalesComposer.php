<?php

namespace TypiCMS\Modules\Core\Composers;

use Illuminate\Contracts\View\View;

class LocalesComposer
{
    /*
     * For back end forms
     */

    public function compose(View $view)
    {
        $view->with('locales', config('translatable.locales'));
        $view->with('admin_locales', config('translatable.admin_locales'));
        $view->with('locale', config('translatable.locale'));
    }
}
