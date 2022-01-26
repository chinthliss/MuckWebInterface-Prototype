<?php

namespace App\Avatar;

use Exception;

/**
 * Utility class to handle loading/holding the layer information required to render an avatar from a PSD file
 * Really not a complete class at all, since we only care about certain parts of a PSD.
 */
class AvatarLayerInformation
{
    private $file;

    private array $layers = [];

    private function getString(int $length): string
    {
        return fread($this->file, $length);
    }

    private function getUnsignedByte(): int
    {
        return unpack("Cvalue", fread($this->file, 1))['value'];
    }

    private function getUnsignedShort(): int
    {
        return unpack("nvalue", fread($this->file, 2))['value'];
    }

    private function getSignedShort(): int
    {
        $value = unpack("nvalue", fread($this->file, 2))['value'];
        return $value < 32768 ? $value : $value - 65536;

    }

    private function getUnsignedInteger(): int
    {
        return unpack("Nvalue", fread($this->file, 4))['value'];
    }

    private function getPaddedUnicodeString(): string
    {
        //First 4 bytes are the length field in code units (2 per char), not bytes
        $charLength = $this->getUnsignedInteger();
        $encoded = fread($this->file, $charLength * 2);
        //Should unencode it here but we don't actually need to in this project
        return $encoded;

    }

    public function __construct($filePath)
    {
        $this->file = fopen($filePath, 'r');

        //Header, check first line but largely skip
        if ($this->getString(4) !== '8BPS') throw new Exception("Unrecognized PSD file format.");
        fseek($this->file, 22, SEEK_CUR);

        //Color Mode Data Section - skipped
        $colorModeLength = $this->getUnsignedInteger();
        if ($colorModeLength) fseek($this->file, $colorModeLength, SEEK_CUR);

        //Image Resources Section - skipped
        $imageResourcesLength = $this->getUnsignedInteger();
        if ($imageResourcesLength) fseek($this->file, $imageResourcesLength, SEEK_CUR);

        //Layer and Mask information section
        fseek($this->file, 8, SEEK_CUR); // Skip section length and layer info length
        $layerCount = abs($this->getSignedShort());

        for($i = 0; $i < $layerCount; $i++) {

            $layer = [];
            $layer['top'] = $this->getUnsignedInteger();
            $layer['left'] = $this->getUnsignedInteger();
            $layer['bottom'] = $this->getUnsignedInteger();
            $layer['right'] = $this->getUnsignedInteger();

            // Channels
            $channelCount = $this->getUnsignedShort();
            $layer['channels'] = [];
            for($channelIndex = 0; $channelIndex < $channelCount; $channelIndex++) {
                $layer['channels'][] = [
                    'id' => $this->getSignedShort(),
                    'length' => $this->getUnsignedInteger()
                ];
            }

            $layer['blendModeSignature'] = $this->getString(4);
            $layer['blendMode'] = $this->getString(4);

            $layer['opacity'] = $this->getUnsignedByte();
            $layer['clipping'] = $this->getUnsignedByte();
            $layer['flags'] = $this->getUnsignedByte();
            $layer['filler'] = $this->getUnsignedByte();

            //Extra layers
            $layer['extra'] = [];
            $totalExtraLength = $this->getUnsignedInteger();

            $layerMaskLength = $this->getUnsignedInteger();
            fseek($this->file, $layerMaskLength, SEEK_CUR);

            $layerBlendingRangeLength = $this->getUnsignedInteger();
            fseek($this->file, $layerBlendingRangeLength, SEEK_CUR);

            $layerNameLength = $this->getUnsignedByte();
            //The name field is padded to 4 bytes, which includes the byte for the name length.
            $layerNameLengthPadded = ceil(($layerNameLength + 1) / 4) * 4;
            $layer['name'] = $this->getString($layerNameLength);
            fseek($this->file, $layerNameLengthPadded - $layerNameLength - 1, SEEK_CUR);

            // Remaining extra data is the total length minus the above separate lengths AND the 2 x 4 bytes holding their lengths
            $extraDataLengthRemaining = $totalExtraLength - $layerMaskLength - $layerBlendingRangeLength - $layerNameLengthPadded - 8;

            while ($extraDataLengthRemaining > 0) {
                $nextSignature = $this->getString(4);
                if ($nextSignature !== '8BIM') throw new Exception('Unexpected signature parsing extra data: ' . $nextSignature);

                $nextKey = $this->getString(4);
                $nextLength = $this->getUnsignedInteger();
                // echo "Key $nextKey, Length $nextLength\n";
                switch ($nextKey) { // Promote anything we're interested in
                    case 'grdm': // Gradient Map
                        // dd(fread($this->file, $nextLength));
                        $gradientMap['version'] = $this->getUnsignedShort();
                        $gradientMap['reversed'] = $this->getUnsignedByte();
                        $gradientMap['dithered'] = $this->getUnsignedByte();
                        $gradientMap['name'] = $this->getPaddedUnicodeString();

                        //Color stops
                        $colorStopLength = $this->getUnsignedShort();
                        $colorStops = [];
                        for ($colorStopi = 0; $colorStopi < $colorStopLength; $colorStopi++) {
                            $colorStop = [];
                            $colorStop['location'] = $this->getUnsignedInteger();
                            $colorStop['midpoint'] = $this->getUnsignedInteger();
                            $colorStop['mode'] = $this->getUnsignedShort();
                            $colorStop['r'] = $this->getUnsignedShort() & 0xff;
                            $colorStop['g'] = $this->getUnsignedShort() & 0xff;;
                            $colorStop['b'] = $this->getUnsignedShort() & 0xff;;
                            $colorStop['a'] = $this->getUnsignedShort() & 0xff;;
                            fseek($this->file, 2, SEEK_CUR); // Unknown what this is
                            $colorStops[] = $colorStop;
                        }
                        $gradientMap['colorStops'] = $colorStops;

                        //Transparency stops
                        $transparencyStopsLength = $this->getUnsignedShort();
                        fseek($this->file, $transparencyStopsLength * 10, SEEK_CUR);

                        //Rest of the gradient map that we don't care about
                        fseek($this->file, 42, SEEK_CUR);

                        $layer['gradientMap'] = $gradientMap;
                        //dd(fread($this->file, 64));
                        dd($gradientMap);
                        break;

                    default:
                        // Place anything unrecognized in the extra array by its key
                        $nextData = fread($this->file, $nextLength);
                        $layer['extra'][$nextKey] = [$nextData];
                        break;
                }

                //Remove length + 12 bytes for the signature, key and length values
                $extraDataLengthRemaining -= 12 + $nextLength;
            }
            $this->layers[] = $layer;
        }

        // Not processing any further, we only want the layer information
        fclose($this->file);
        unset ($this->file);
    }

    public function toArray(): array
    {
        return $this->layers;
    }
}
