<?php

namespace App\Events;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event as Events;
use Laracord\Events\Event;

class InviteLinkEvent extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::MESSAGE_CREATE;

    /**
     * Handle the event.
     */
    public function handle(Message $message, Discord $discord)
    {
        if (
            $message->author->id === $discord->id ||
            ! $message->guild ||
            $message->author->id === $message->guild->owner_id
        ) {
            return;
        }

        if (! preg_match('/discord\.gg\/([a-zA-Z0-9]+)(?:\s|$)/', $message->content, $matches)) {
            return;
        }

        $invites = collect($message->guild->invites)->pluck('code');

        if ($invites->contains($invite = $matches[1])) {
            return;
        }

        return $this
            ->message("🔗 **{$message->author}** shared an invite link in {$message->channel}.")
            ->field('Invite Link', "https://discord.gg/{$invite}")
            ->thumbnail($message->author->avatar)
            ->send('1204736291468738581')
            ->then(fn () => $message->delete());
    }
}
