<?php

namespace App\Commands;

use Discord\Builders\Components\Button;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Command;

class PingCommand extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'ping';

    /**
     * The command description.
     *
     * @var string|null
     */
    protected $description = 'Ping? Pong!';

    /**
     * Handle the command.
     *
     * @param  \Discord\Parts\Channel\Message  $message
     * @param  array  $args
     * @return void
     */
    public function handle($message, $args)
    {
        return $this
            ->message('Ping? Pong!')
            ->title('Ping')
            ->field('Response time', $message->timestamp->diffForHumans(null, true))
            ->button('Laracord Resources', route: 'resources', emoji: '💻', style: Button::STYLE_SECONDARY)
            ->reply($message);
    }

    /**
     * The command interaction routes.
     */
    public function interactions(): array
    {
        return [
            'resources' => fn (Interaction $interaction) => $this
                ->message('Check out the resources below to learn more about Laracord.')
                ->title('Laracord Resources')
                ->buttons([
                    'Documentation' => 'https://laracord.com',
                    'GitHub' => 'https://github.com/laracord/laracord',
                ])
                ->reply($interaction, ephemeral: true),
        ];
    }
}
