<?php

namespace App\Avatar;

use Exception;

/**
 * Utility class to handle loading things we want for an Avatar Doll from a PSD file.
 * It isn't even close to reading a full PSD file! Pretty much stops after pulling all the layer information.
 */
class AvatarDollPsdReader
{
    /**
     * @var resource File handle being acted upon
     */
    private static $file;

    public function __construct()
    {
        throw new Exception("This class should not be instantiated.");
    }

    private static function getString(int $length): string
    {
        return fread(self::$file, $length);
    }

    private static function getUnsignedByte(): int
    {
        return unpack("Cvalue", fread(self::$file, 1))['value'];
    }

    private static function getUnsignedShort(): int
    {
        return unpack("nvalue", fread(self::$file, 2))['value'];
    }

    private static function getSignedShort(): int
    {
        $value = unpack("nvalue", fread(self::$file, 2))['value'];
        return $value < 32768 ? $value : $value - 65536;

    }

    private static function getUnsignedInteger(): int
    {
        return unpack("Nvalue", fread(self::$file, 4))['value'];
    }

    private static function getPaddedUnicodeString(): string
    {
        //First 4 bytes are the length field in code units (2 per char), not bytes
        $charLength = self::getUnsignedInteger();
        $encoded = fread(self::$file, $charLength * 2);
        //Should decode it here but we don't actually need to in this project
        return $encoded;

    }

    public static function loadFromFile($filePath): array
    {
        $result = [
            'layers' => [],
            'gradients' => []
        ];

        self::$file = fopen($filePath, 'r');

        //Header, check first line but largely skip
        if (self::getString(4) !== '8BPS') throw new Exception("Unrecognized PSD file format.");
        fseek(self::$file, 22, SEEK_CUR);

        //Color Mode Data Section - skipped
        $colorModeLength = self::getUnsignedInteger();
        if ($colorModeLength) fseek(self::$file, $colorModeLength, SEEK_CUR);

        //Image Resources Section - skipped
        $imageResourcesLength = self::getUnsignedInteger();
        if ($imageResourcesLength) fseek(self::$file, $imageResourcesLength, SEEK_CUR);

        //Layer and Mask information section
        fseek(self::$file, 8, SEEK_CUR); // Skip section length and layer info length
        $layerCount = abs(self::getSignedShort());

        for($i = 0; $i < $layerCount; $i++) {

            $layer = [];
            $layer['top'] = self::getUnsignedInteger();
            $layer['left'] = self::getUnsignedInteger();
            $layer['bottom'] = self::getUnsignedInteger();
            $layer['right'] = self::getUnsignedInteger();

            // Channels
            $channelCount = self::getUnsignedShort();
            $layer['channels'] = [];
            for($channelIndex = 0; $channelIndex < $channelCount; $channelIndex++) {
                $layer['channels'][] = [
                    'id' => self::getSignedShort(),
                    'length' => self::getUnsignedInteger()
                ];
            }

            $layer['blendModeSignature'] = self::getString(4);
            $layer['blendMode'] = self::getString(4);

            $layer['opacity'] = self::getUnsignedByte();
            $layer['clipping'] = self::getUnsignedByte();
            $layer['flags'] = self::getUnsignedByte();
            $layer['filler'] = self::getUnsignedByte();

            //Extra layers
            $totalExtraLength = self::getUnsignedInteger();

            $layerMaskLength = self::getUnsignedInteger();
            fseek(self::$file, $layerMaskLength, SEEK_CUR);

            $layerBlendingRangeLength = self::getUnsignedInteger();
            fseek(self::$file, $layerBlendingRangeLength, SEEK_CUR);

            $layerNameLength = self::getUnsignedByte();
            //The name field is padded to 4 bytes, which includes the byte for the name length.
            $layerNameLengthPadded = ceil(($layerNameLength + 1) / 4) * 4;
            $layer['name'] = self::getString($layerNameLength);
            fseek(self::$file, $layerNameLengthPadded - $layerNameLength - 1, SEEK_CUR);

            // Remaining extra data is the total length minus the above separate lengths AND the 2 x 4 bytes holding their lengths
            $extraDataLengthRemaining = $totalExtraLength - $layerMaskLength - $layerBlendingRangeLength - $layerNameLengthPadded - 8;

            while ($extraDataLengthRemaining > 0) {
                $nextSignature = self::getString(4);
                if ($nextSignature !== '8BIM') throw new Exception('Unexpected signature parsing extra data: ' . $nextSignature);

                $nextKey = self::getString(4);
                $nextLength = self::getUnsignedInteger();
                // echo "Key $nextKey, Length $nextLength\n";
                switch ($nextKey) { // Promote anything we're interested in
                    case 'grdm': // Gradient Map
                        // dd(fread(self::$file, $nextLength));
                        $gradientMap = [];
                        $gradientMap['version'] = self::getUnsignedShort();
                        $gradientMap['reversed'] = self::getUnsignedByte();
                        $gradientMap['dithered'] = self::getUnsignedByte();
                        $gradientMap['name'] = self::getPaddedUnicodeString();

                        //Color stops
                        $gradientMap['colorStops'] = [];
                        $colorStopLength = self::getUnsignedShort();
                        for ($colorStopi = 0; $colorStopi < $colorStopLength; $colorStopi++) {
                            $colorStop = [];
                            $colorStop['location'] = (self::getUnsignedInteger() / 4096.0) * 255;
                            $colorStop['midpoint'] = self::getUnsignedInteger();
                            $colorStop['mode'] = self::getUnsignedShort();
                            $colorStop['r'] = (self::getUnsignedShort() / 65535.0) * 255;
                            $colorStop['g'] = (self::getUnsignedShort() / 65535.0) * 255;
                            $colorStop['b'] = (self::getUnsignedShort() / 65535.0) * 255;
                            $colorStop['a'] = self::getUnsignedShort() & 0xff;
                            fseek(self::$file, 2, SEEK_CUR); // Unknown what this is
                            $gradientMap['colorStops'][] = $colorStop;
                        }

                        //Transparency stops
                        $transparencyStopsLength = self::getUnsignedShort();
                        fseek(self::$file, $transparencyStopsLength * 10, SEEK_CUR);

                        //Rest of the gradient map that we don't care about
                        fseek(self::$file, 42, SEEK_CUR);

                        //For the purpose of this, we want to collect them separately but still need the layer name
                        $gradientMap['layer'] = $layer['name'];
                        $result['gradients'][] = $gradientMap;
                        //dd(fread(self::$file, 64));
                        //dd($gradientMap);
                        break;

                    default:
                        // Any extra we haven't written processing for, we just skip
                        fseek(self::$file, $nextLength, SEEK_CUR);
                        break;
                }

                //Remove length + 12 bytes for the signature, key and length values
                $extraDataLengthRemaining -= 12 + $nextLength;
            }
            $result['layers'][] = $layer;
        }

        // Not processing any further, we only want the layer information
        fclose(self::$file);
        self::$file = null;

        return $result;
    }
}
