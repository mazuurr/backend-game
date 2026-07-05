<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;

class DateHelper extends Helper
{
    public function format(?string $dateString, string $format = 'd.m.Y H:i'): string
    {
        if (empty($dateString)) {
            return '—';
        }
        try {
            return (new \DateTime($dateString))->format($format);
        } catch (\Exception) {
            return $dateString;
        }
    }
}
