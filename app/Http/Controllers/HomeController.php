<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    public function show(Request $request)
    {
        if ($request->has('refer')) {
            $request->session()->put('account.referral', $request->input('refer'));
        }
        return view('home');
    }

    public function showRoadmap()
    {
        // Hosting this here since it's only a temporary thing.
        $roadmap = [
            [
                'title' => 'Styling/Theming',
                'description' => 'Build up a common set of styles to use throughout the site. This includes looking at the page layout and how it breaks down on mobile/tablets.',
                'progress' => 'Prototype done. Need to revisit in final version.'
            ],
            [
                'title' => 'Browser Notifications',
                'description' => 'Allow game notifications to become browser notifications. This would be opt-in from the notifications screen rather than popping up on visiting the site. ',
                'progress' => 'Delayed until later.'
            ],
            [
                'title' => 'Socialite for third party logins',
                'description' => 'Allow logins from things like Facebook or Twitter.',
                'progress' => 'Delayed until later.'
            ],
            [
                'title' => 'Payment integrations',
                'description' => 'Overhaul of the payment system to unify it and to bring it up to date with latest vendor requirements.',
                'progress' => 'Done but pending being able to test extensively.'
            ],
            [
                'title' => 'Patreon Integration',
                'description' => 'Allow patreon support to give a bonus in-game. Re-write of the existing system (which is updated by manual tasks and requires manual claiming) to automate updates AND claiming.',
                'progress' => 'All set up. Needs automation.'
            ],
            [
                'title' => 'Connect',
                'description' => 'Allow a webpage to control the muck via websockets. Re-write of the existing system which uses Flash. Also want to tie into this framework so it logs in as the active character.',
                'progress' => 'Pending writing a client, though a simple version is now in use on the existing website.'
            ],
            [
                'title' => 'Websocket',
                'description' => 'Rewrite of the present websocket connection to the muck to work within this framework. Also stripping off backup websocket functionality since websockets are now prevalent. ',
                'progress' => 'Completed. ALso now it\'s own project.'
            ],
            [
                'title' => 'Ticket System',
                'description' => 'Rewrite of the ticket system to allow it to work separate to the muck and more cohesively from the web side.',
                'progress' => 'Done but has some outstanding tasks/changes: Muck side functionality; rewrite of how voting works so it isn\'t immutable; make suggestions their own top level category'
            ],
            [
                'title' => 'Character Dashboard',
                'description' => "Screen to show all of a player's characters along with live tracking of stats. Allow re-ordering of characters",
                'progress' => 'Started, presently just a list of characters'
            ],
            [
                'title' => 'Character Profile',
                'description' => "View somebody else's character. Note to self - look at including pinfo and view.",
                'progress' => 'Done bar further improvements / additions'
            ],
            [
                'title' => 'Avatar editing',
                'description' => 'Allow the viewing and editing of unique avatars per character.',
                'progress' => 'Done. Editor badly needs a second pass but is feature complete. Also needs a caching mechanism.'
            ]
        ];
        return view('roadmap')->with([
            'phase' => 'Core Functionality',
            'phaseDescription' => "At the moment the focus is on the backbone parts of the site, such as account functionality and underlying services. The intent is to largely avoid game-related content until later, though there is some cross over with parts of the game that effect other content (E.g. character generation acts as a gate to many pages so needs to be in place).",
            'future' => "After the core functionality is finished, moving onto multiplayer content. At the moment the intent is to focus next on the various editors (e.g. monster editor) first.",
            'roadmap' => $roadmap
        ]);
    }
}
