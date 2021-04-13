<?php


namespace App\Helpers;

use Illuminate\Support\HtmlString;

class Breadcrumbs
{
    /**
     * Breadcrumbs expected in the format {route, label}
     * @param array $breadcrumbs
     * @return HtmlString
     */
    public static function render(array $breadcrumbs)
    {
        $listItems = [];
        foreach ($breadcrumbs as $item) {
            array_push($listItems, '<li class="breadcrumb-item active" aria-current="page">' . $item['label'] . '</li>');
        }
        return new HtmlString(
        '<nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-dark">' . join('', $listItems) . '</ol>
            </nav>');
    }
}
