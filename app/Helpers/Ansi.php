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
        $remaining = $string;
        $inTag = false;

        while ($remaining) {
            $nextIndex = strpos($remaining, '^');
            if ($nextIndex === false) {
                $nextFragment = $remaining;
                $remaining = "";
            } else {
                $nextFragment = substr($remaining, 0, $nextIndex);
                $remaining = substr($remaining, $nextIndex + 1);
            }

            echo "Index = $nextIndex\n";
            echo "  Next      = " . $nextFragment . "\n";
            echo "  Remaining = " . $remaining . "\n";
            echo "  inTag     = $inTag\n";


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
                } else {
                    //Any non-match is treated as a reset
                    $foreground = null;
                    $background = null;
                    $other = [];
                }
                $inTag = false;
            } else {
                // If the next character is also a ^ we need to take it as an escaped one and skip.
                // This is only available whilst we're not parsing a tag.
                if ($remaining && $remaining[0] === '^') {
                    $nextFragment = $nextFragment . '^';
                    $remaining = substr($remaining, 1);
                } else { // Otherwise we're parsing a tag next
                    $inTag = true;
                }

                // Either way, save the present string fragment
                if ($nextFragment) { // Often blank if two styles were used side by side
                    array_push($fragments, [$nextFragment, $foreground, $background, $other]);
                }

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
     * @param string $string String from the muck
     * @return string HTML string represented as spans
     */
    public static function unparsedToHtml(string $string): string
    {
        $fragments = self::NeonAnsiToFragments($string);
        return self::stringFragmentsToHtml($fragments);
    }
}
