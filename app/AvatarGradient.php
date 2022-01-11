<?php

namespace App;

/**
 * Utility class to hold a gradient. Mostly made to keep the hardcoded list in one place.
 */
class AvatarGradient
{
    /**
     * Steps -> 4 float array in the form stepPoint, red, green, blue. Values are normalized
     * @var array[]
     */
    private static array $gradients = [
        'Blonde' => [
            'desc' => "As written on the tin.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.980392156862745, 0.980392156862745, 0.823529411764706]
            ]
        ],
        'Brown' => [
            'desc' => "A nice tan.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.541176470588235, 0.317647058823529, 0.203921568627451]
            ],
            'free' => true
        ],
        'Burnt Gold' => [
            'desc' => "Golden yellow/brown with reddish towards the dark.",
            'steps' => [
                [0, 0, 0, 0],
                [0.156862745098039, 0.607843137254902, 0.184313725490196, 0.0784313725490196],
                [0.470588235294118, 0.725490196078431, 0.419607843137255, 0.156862745098039],
                [1, 0.784313725490196, 0.784313725490196, 0.0862745098039216]
            ],
            'free' => true
        ],
        'Burple' => [
            'desc' => "Just what you expect!",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.392156862745098, 0.352941176470588, 1]
            ],
            'free' => true
        ],
        'Chartreuse' => [
            'desc' => "It's Chartreuse!",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.498039215686275, 1, 0]
            ],
            'cost' => 1
        ],
        'Chocolate' => [
            'desc' => "As delicious as it sounds.",
            'steps' => [
                [0, 0.0392156862745098, 0.0392156862745098, 0.0392156862745098],
                [1, 0.545098039215686, 0.270588235294118, 0.0745098039215686]
            ]
        ],
        'Colgate' => [
            'desc' => "It's Colgate!",
            'steps' => [
                [0, 0, 0, 0],
                [0.588235294117647, 1, 1, 1],
                [1, 0, 1, 1]
            ],
            'free' => true
        ],
        'Cotton Candy' => [
            'desc' => "Get lost in a pink cloud.",
            'steps' => [
                [0, 1, 0.588235294117647, 0.807843137254902],
                [1, 1, 0.941176470588235, 1]
            ]
        ],
        'Custard' => [
            'desc' => "Creamy with soft highlights.",
            'steps' => [
                [0, 0, 0, 0],
                [0.784313725490196, 0.811764705882353, 0.811764705882353, 0.705882352941177],
                [1, 1, 1, 1]
            ],
            'free' => true
        ],
        'Cyan-ide' => [
            'desc' => "Nothing like a little neon blue!",
            'steps' => [
                [0, 0, 0.0980392156862745, 0.0980392156862745],
                [0.156862745098039, 0, 0.392156862745098, 0.392156862745098],
                [1, 0, 1, 1]
            ]
        ],
        'Earth' => [
            'desc' => "Soft browns",
            'steps' => [
                [0, 0, 0.117647058823529, 0.0196078431372549],
                [0.117647058823529, 0.470588235294118, 0.392156862745098, 0.156862745098039],
                [1, 0.898039215686275, 0.831372549019608, 0.631372549019608]
            ],
            'free' => true
        ],
        'Eggplant' => [
            'desc' => "A darker violet.",
            'steps' => [
                [0, 0.0392156862745098, 0, 0.0784313725490196],
                [1, 0.372549019607843, 0, 0.764705882352941],
            ]
        ],
        'Electric Yellow' => [
            'desc' => "It's electric, boogie-woogie.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 1, 1, 0.2]
            ]
        ],
        'Flour' => [
            'desc' => "Is that a ghost?",
            'steps' => [
                [0, 0.47843137254902, 0.47843137254902, 0.47843137254902],
                [1, 1, 1, 1]
            ]
        ],
        'Forest Green' => [
            'desc' => "A nice shade of tree green.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.133333333333333, 0.545098039215686, 0.133333333333333]
            ]
        ],
        'Gamma Green' => [
            'desc' => "It's Gamma Green!",
            'steps' => [
                [0, 0, 0, 0],
                [0.0980392156862745, 0.4, 0.4, 0],
                [0.784313725490196, 0, 0.8, 0],
                [1, 0, 0.2, 0.0980392156862745]
            ],
            'free' => true
        ],
        'Gloom' => [
            'desc' => "So very dark.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.0392156862745098, 0.0392156862745098, 0.0392156862745098]
            ],
            'free' => true
        ],
        'Gold' => [
            'desc' => "Not as valuable as you think.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 1, 0.843137254901961, 0]
            ]
        ],
        'Goldenrod' => [
            'desc' => "A nice bright gold.",
            'steps' => [
                [0, 0, 0, 0],
                [0.0196078431372549, 0.00784313725490196, 0.00784313725490196, 0.00784313725490196],
                [1, 1, 0.843137254901961, 0]
            ],
            'free' => true
        ],
        'Grey, Steel' => [
            'desc' => "Light Steel Blue",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.329411764705882, 0.329411764705882, 0.329411764705882]
            ]
        ],
        'Greyscale' => [
            'desc' => "This is the second gradient!",
            'steps' => [
                [0, 0, 0, 0],
                [1, 1, 1, 1]
            ],
            'free' => true
        ],
        'Hellish' => [
            'desc' => "Hellish fire gradients! Release the evil within!",
            'steps' => [
                [0, 0.215686274509804, 0.0392156862745098, 0.0392156862745098],
                [0.196078431372549, 1, 0, 0],
                [1, 1, 1, 0]
            ]
        ],
        'Hot Pink' => [
            'desc' => "Hot pink, for all those mutants that enjoy their feminine colors! Flamboyantly so!",
            'steps' => [
                [0, 0.219607843137255, 0, 0.0862745098039216],
                [0.129411764705882, 0.219607843137255, 0, 0.0862745098039216],
                [0.301960784313725, 0.592156862745098, 0.0196078431372549, 0.411764705882353],
                [0.698039215686274, 0.882352941176471, 0.0549019607843137, 0.501960784313725],
                [1, 0.996078431372549, 0.435294117647059, 0.737254901960784]
            ]
        ],
        'Hunter Green' => [
            'desc' => "Deepest of greens.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.207843137254902, 0.368627450980392, 0.231372549019608]
            ]
        ],
        'Indian-Red' => [
            'desc' => "A dark red.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.690196078431373, 0.0901960784313725, 0.12156862745098]
            ]
        ],
        'Infernal' => [
            'desc' => "Gloomy with tinges of red.",
            'steps' => [
                [0, 0, 0, 0],
                [0.784313725490196, 0.0784313725490196, 0.0784313725490196, 0.0784313725490196],
                [1, 0.392156862745098, 0.0784313725490196, 0.0784313725490196]
            ]
        ],
        'Klein Blue' => [
            'desc' => "An electric shade of blue.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0, 0.184313725490196, 0.654901960784314]
            ],
            'free' => true
        ],
        'Lawn Green' => [
            'desc' => "Tastes like grass!",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.486274509803922, 0.988235294117647, 0]
            ]
        ],
        'Light Tan' => [
            'desc' => "A hint of brown",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.949019607843137, 0.674509803921569, 0.466666666666667]
            ]
        ],
        'Lust' => [
            'desc' => "Feeling a little flushed?",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.901960784313726, 0.125490196078431, 0.125490196078431]
            ]
        ],
        'Medium Orchid' => [
            'desc' => "Not too much orchid!",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.729411764705882, 0.333333333333333, 0.827450980392157]
            ],
            'free' => true
        ],
        'Midnight Purple' => [
            'desc' => "Dark purple smoothness.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.156862745098039, 0.00392156862745098, 0.215686274509804]
            ]
        ],
        'Mint Cream' => [
            'desc' => "Just a bit off white.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.882352941176471, 1, 0.901960784313726]
            ]
        ],
        'Mint Frosting' => [
            'desc' => "As tasty as it is tasteful.",
            'steps' => [
                [0, 0.243137254901961, 0.705882352941177, 0.537254901960784],
                [1, 0.96078431372549, 1, 0.980392156862745]
            ]
        ],
        'Neon Orange' => [
            'desc' => "It's Neon Orange!",
            'steps' => [
                [0, 0, 0, 0],
                [0.156862745098039, 0.392156862745098, 0.156862745098039, 0.0784313725490196],
                [1, 0.992156862745098, 0.372549019607843, 0]
            ]
        ],
        'Olivedrab' => [
            'desc' => "Drabbest green you ever saw!",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.419607843137255, 0.556862745098039, 0.137254901960784]
            ]
        ],
        'Orchid' => [
            'desc' => "A light purple.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.854901960784314, 0.43921568627451, 0.83921568627451]
            ],
            'free' => true
        ],
        'Peach' => [
            'desc' => "You're looking a bit fruity today!",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.937254901960784, 0.815686274509804, 0.811764705882353]
            ],
            'free' => true
        ],
        'Peacock' => [
            'desc' => "A night shade of blue.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.2, 0.631372549019608, 0.788235294117647]
            ],
            'cost' => 1
        ],
        'Periwinkle' => [
            'desc' => "A kind of blue/violet.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.8, 0.8, 1]
            ],
            'free' => true
        ],
        'Pinky Pink' => [
            'desc' => "A rich pink.",
            'steps' => [
                [0, 1, 0.588235294117647, 0.807843137254902],
                [1, 1, 0.392156862745098, 0.745098039215686],
            ]
        ],
        'Psychedelic' => [
            'desc' => "The colors man, the colors!",
            'steps' => [
                [0, 0, 0, 0],
                [0.141176470588235, 1.0, 0.0, 0.0],
                [0.282352941176471, 1, 0.647058823529412, 0],
                [0.423529411764706, 1, 1, 0],
                [0.564705882352941, 0, 0.392156862745098, 0],
                [0.705882352941177, 0, 0, 1],
                [0.847058823529412, 0.435294117647059, 0, 1],
                [1, 0.56078431372549, 0, 1]
            ]
        ],
        'Raspberry' => [
            'desc' => "Mmm, delicious.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.529411764705882, 0.149019607843137, 0.341176470588235]
            ]
        ],
        'Reverse Greyscale' => [
            'desc' => "Let's go backwards!",
            'steps' => [
                [0, 1, 1, 1],
                [1, 0, 0, 0]
            ],
            'cost' => 1
        ],
        'Royal Blue' => [
            'desc' => "Deep as the sea itself.",
            'steps' => [
                [0, 0.0392156862745098, 0.0392156862745098, 0.0392156862745098],
                [1, 0, 0.137254901960784, 0.4]
            ]
        ],
        'Silver' => [
            'desc' => "A soft gray.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.752941176470588, 0.752941176470588, 0.752941176470588]
            ],
            'free' => true
        ],
        'Sinister' => [
            'desc' => "Dark with red highlights.",
            'steps' => [
                [0, 0, 0, 0],
                [0.196078431372549, 0.490196078431373, 0.196078431372549, 0.0196078431372549],
                [1, 1, 0.196078431372549, 0.0196078431372549]
            ]
        ],
        'Sky Blue' => [
            'desc' => "A soft sky blue.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.529411764705882, 0.807843137254902, 0.980392156862745]
            ],
            'free' => true
        ],
        'Slate Blue' => [
            'desc' => "Slate Blue",
            'steps' => [
                [0, 0, 0, 0],
                [0.996078431372549, 0.443137254901961, 0.443137254901961, 0.776470588235294],
                [1, 1, 1, 1]
            ]
        ],
        'Tanned' => [
            'desc' => "Just a bit tan.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.941176470588235, 0.556862745098039, 0.372549019607843]
            ]
        ],
        'Tiger-Orange' => [
            'desc' => "Rawr!",
            'steps' => [
                [0, 0, 0, 0],
                [0.156862745098039, 0.588235294117647, 0.235294117647059, 0.0784313725490196],
                [0.470588235294118, 0.745098039215686, 0.372549019607843, 0.0784313725490196],
                [1, 1, 0.450980392156863, 0]
            ]
        ],
        'Toxic Green' => [
            'desc' => "Toxicity levels may vary!",
            'steps' => [
                [0, 0.0470588235294118, 0.0980392156862745, 0],
                [0.156862745098039, 0.392156862745098, 0.607843137254902, 0],
                [1, 0.607843137254902, 1, 0]
            ]
        ],
        'Tyl Purple' => [
            'desc' => "Tyl likes this shade, you should too.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.549019607843137, 0.294117647058824, 0.549019607843137]
            ],
            'free' => true
        ],
        'Ultra Violet' => [
            'desc' => "So Violet, we had to get a special permit.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 0.607843137254902, 0, 0.988235294117647]
            ]
        ],
        'Umbral' => [
            'desc' => "Purple and black starscape.",
            'steps' => [
                [0, 0, 0, 0],
                [0.490196078431373, 0.549019607843137, 0.294117647058824, 0.549019607843137],
                [0.784313725490196, 0, 0, 0],
                [1, 0.549019607843137, 0.294117647058824, 0.549019607843137]
            ],
            'free' => true
        ],
        'Yellow' => [
            'desc' => "What it says on the tin.",
            'steps' => [
                [0, 0, 0, 0],
                [1, 1, 1, 0]
            ],
            'cost' => 1
        ]
    ];

    public function __construct(
        public string $name,
        public string $desc,
        public array  $steps
    )
    {

    }

    public static function fromArray(array $array) : AvatarGradient
    {
        return new AvatarGradient($array['name'], $array['desc'], $array['steps']);
    }

    public static function getGradientData(): array
    {
        //Temporary measure to emulate a row from a database
        $gradients = self::$gradients;
        foreach($gradients as $name => $gradient) {
            $gradients[$name]['name'] = $name;
        }
        return $gradients;
    }

}
