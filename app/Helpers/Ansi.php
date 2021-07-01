<?php


namespace App\Helpers;

use Illuminate\Support\HtmlString;

/**
 * Class Ansi
 * This is pretty much only a separate class since it might get expanded upon at some point.
 * Presently only supports Neon-style tags ('1 parse_ansi').
 * @package App\Helpers
 */
class Ansi
{

    //Lookup in the format [foreground, background, decorator]. Null if it doesn't apply anything.
    private static $ansi_neon = [
        // Making no attempt to match normal / reset as any unmatched code resets styles so we can treat it as such

        // ANSI Foreground
        'gloom' => ['gloom', null, null],
        'red' => ['red', null, null],
        'green' => ['green', null, null],
        'yellow' => ['yellow', null, null],
        'blue' => ['blue', null, null],
        'purple' => ['purple', null, null],
        'cyan' => ['cyan', null, null],
        'white' => ['white', null, null],
        'black' => ['black', null, null],
        'crimson' => ['crimson', null, null],
        'forest' => ['forest', null, null],
        'brown' => ['brown', null, null],
        'navy' => ['navy', null, null],
        'violet' => ['violet', null, null],
        'aqua' => ['aqua', null, null],
        'gray' => ['gray', null, null],

        // ANSI Background
        'bblack' => [null, 'black', null],
        'bred' => [null, 'red', null],
        'bgreen' => [null, 'green', null],
        'byellow' => [null, 'yellow', null],
        'bblue' => [null, 'blue', null],
        'bpurple' => [null, 'purple', null],
        'bcyan' => [null, 'cyan', null],
        'bgray' => [null, 'gray', null],

        // Special
        'invert' => [null, null, 'invert'],
        'bold' => [null, null, 'bold'],
        'underline' => [null, null, 'underline'],
    ];

    /**
     * Returns [foreground, background, other]
     * @param string $code
     * @return array|null
     */
    private static function matchAnsiCode(string $code): ?array
    {
        $code = strtolower($code);
        return array_key_exists($code, self::$ansi_neon) ? self::$ansi_neon[$code] : null;
    }

    /**
     * Returns an array of fragments of a string in the form [text, foreground, background, [otherDecoration]]
     * @param string $string
     * @return array
     */
    private static function NeonAnsiToFragments(string $string): array
    {
        $foreground = null;
        $background = null;
        $other = [];

        // Initially breaking the string into fragments of [content, foreground, background]
        $fragments = [];
        $lastFragment = null;
        $inTag = false;

        foreach (explode('^', $string) as $nextFragment) {
            // Special case - If we're in a tag and it's empty, treat it as an escaped character
            if ($inTag && $nextFragment === '') {
                $inTag = false;
                $nextFragment = '^';
            }
            if ($inTag) {
                // Need to match a code and update present styling
                $nextTag = self::matchAnsiCode($nextFragment);
                if ($nextTag) {
                    if ($nextTag[0]) $foreground = $nextTag[0];
                    if ($nextTag[1]) $background = $nextTag[1];
                    if ($nextTag[2]) {
                        if ($nextTag[2] == 'invert') { // Special case where we switch fore/back
                            $foregroundOld = $foreground;
                            $foreground = $background;
                            $background = $foregroundOld;
                        } else {
                            if (!in_array($nextTag[2], $other))
                                array_push($other, $nextTag[2]);
                        }
                    }
                } else { //Any non-match is treated as a reset
                    $foreground = null;
                    $background = null;
                    $other = [];
                }
                $inTag = false;
            } else {
                if ($nextFragment) { // Often blank if two styles were used side by side
                    // Maybe combine with the previous fragment if they're identical in style
                    if (
                        $lastFragment
                        && $lastFragment[1] === $foreground
                        && $lastFragment[2] === $background
                        && $lastFragment[3] == $other
                    )
                        $lastFragment[0] .= $nextFragment;
                    else
                        $fragments[] = [$nextFragment, $foreground, $background, $other];

                    $lastFragment = &$fragments[count($fragments) - 1];
                }
                if ($nextFragment !== '^') $inTag = true;
            }
        }
        return $fragments;
    }

    private static function stringFragmentsToHtml(array $fragments): HtmlString
    {
        $string = "";
        foreach ($fragments as $fragment) {
            $classList = [];
            if ($fragment[1]) array_push($classList, 'fg-' . $fragment[1]);
            if ($fragment[2]) array_push($classList, 'bg-' . $fragment[2]);
            if ($fragment[3]) $classList = array_merge($classList, $fragment[3]);
            if (count($classList) > 0)
                $string .= '<span class="' . join(' ', $classList) . '">' . $fragment[0] . '</span>';
            else
                $string .= $fragment[0];
        }
        return new HtmlString($string);
    }

    /**
     * Converts a string containing strings like ^yellow^ into HTML spans that use potential CSS classes instead.
     * Built to emulate the muck, so looks for strings between ^'s. Doubling up escapes such.
     * @param string $string String from the muck.
     * @param bool $escapeInputHtml Whether to escape HTML in the string being parsed, defaults to true.
     * @return string HTML string represented as spans
     */
    public static function unparsedToHtml(string $string, bool $escapeInputHtml = true): string
    {
        if ($escapeInputHtml) $string = htmlspecialchars($string);
        $fragments = self::NeonAnsiToFragments($string);
        return self::stringFragmentsToHtml($fragments);
    }
}
