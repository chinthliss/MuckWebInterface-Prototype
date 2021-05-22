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
    public static function render(array $breadcrumbs) : HtmlString
    {
        $listItems = [];
        foreach ($breadcrumbs as $item) {
            if (isset($item['route']))
                $innerContent = '<a href="' . route($item['route']) . '">' . $item['label'] . '</a>';
            else
                $innerContent = $item['label'];
            array_push($listItems, '<li class="breadcrumb-item active" aria-current="page">' . $innerContent . '</li>');
        }
        return new HtmlString('<ol class="breadcrumb">' . join('', $listItems) . '</ol>');
    }
}
