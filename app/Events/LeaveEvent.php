<?php

namespace App\Events;

use Discord\Discord;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event as Events;
use Illuminate\Support\Arr;
use Laracord\Events\Event;

class LeaveEvent extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::GUILD_MEMBER_REMOVE;

    /**
     * The guilds to fire this event for.
     *
     * @var array
     */
    protected $guilds = [
        '1204732258053922816' => '1204736291468738581',
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
            ->message("**{$member->username}** has left the Discord server.")
            ->send($this->guilds[$member->guild_id]);
    }
}
