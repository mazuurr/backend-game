<?php
declare(strict_types=1);

namespace App\View;

use Cake\View\View;

class AppView extends View
{
    public function initialize(): void
    {
        $this->addHelper('Paginator');
        $this->addHelper('Date');
        $this->addHelper('Form', [
            'className' => 'AdminLTE.Form',
        ]);
    }
}
