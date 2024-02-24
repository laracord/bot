<?php

namespace App\Events;

use Discord\Discord;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event as Events;
use Illuminate\Support\Arr;
use Laracord\Events\Event;

class WelcomeEvent extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::GUILD_MEMBER_ADD;

    /**
     * The guilds to fire this event for.
     *
     * @var array
     */
    protected $guilds = [
        '1204732258053922816' => '1204733703410942002',
    ];

    /**
     * Handle the event.
     */
    public function handle(Member $member, Discord $discord)
    {
        if (! Arr::has($this->guilds, $member->guild_id)) {
            return;
        }

        $this
            ->message("Welcome to the Laracord Discord, **{$member->username}**!")
            ->title("👋 Welcome {$member->username}")
            ->send($this->guilds[$member->guild_id]);
    }
}
