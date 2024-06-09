<?php

namespace App\Commands;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\TextInput;
use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Command;

class SinkCommand extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'sink';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'A kitchen sink of examples.';

    /**
     * The fruit menu options.
     */
    protected array $fruits = [
        'apples' => [
            'label' => 'Apples',
            'description' => 'A tasty red fruit.',
            'emoji' => '🍎',
            'default' => true,
        ],
        'oranges' => [
            'label' => 'Oranges',
            'description' => 'A tasty orange fruit.',
            'emoji' => '🍊',
        ],
        'bananas' => [
            'label' => 'Bananas',
            'description' => 'A tasty yellow fruit.',
            'emoji' => '🍌',
        ],
    ];

    /**
     * Handle the command.
     *
     * @param  \Discord\Parts\Channel\Message  $message
     * @param  array  $args
     * @return void
     */
    public function handle($message, $args)
    {
        $this
            ->message('Here is a kitchen sink of examples.')
            ->title('Kitchen Sink')
            ->thumbnail('https://i.imgur.com/XettmeQ.png')
            ->color('#e5392a')
            ->field('Uptime', $this->bot()->getUptime()->diffForHumans(), inline: false)
            ->fields([
                'Commands' => count($this->bot()->getRegisteredCommands()),
                'Guilds' => $this->discord()->guilds->count(),
                'Users' => $this->discord()->users->count(),
            ])
            ->button('Example Interaction', route: 'wave', emoji: '👋', style: 'success')
            ->button('Example Modal', route: 'modal', emoji: '📦', style: 'secondary')
            ->button('View Source', 'https://github.com/laracord/bot/blob/main/app/Commands/SinkCommand.php', emoji: '🖥️')
            ->select($this->fruits, route: 'select', placeholder: 'Select a fruit...')
            ->select(type: 'channel', route: 'select:channel', placeholder: 'Select a channel...', minValues: 2, maxValues: 3, options: ['channelTypes' => [Channel::TYPE_GUILD_TEXT]])
            ->select(type: 'role', route: 'select:role', placeholder: 'Select a role...')
            ->select(type: 'user', route: 'select:user', placeholder: 'Select a user...')
            ->timestamp()
            ->reply($message);
    }

    /**
     * The command interaction routes.
     */
    public function interactions(): array
    {
        return [
            'wave' => fn (Interaction $interaction) => $this->handleWave($interaction),
            'modal' => fn (Interaction $interaction) => $this->showModal($interaction),
            'select:{type?}' => fn (Interaction $interaction, ?string $type = null) => $this->handleSelect($interaction, $type),
        ];
    }

    /**
     * Handle the wave interaction.
     */
    protected function handleWave(Interaction $interaction): void
    {
        $this
            ->message("👋 Hello {$interaction->member->__toString()}!")
            ->title('Wave')
            ->reply($interaction, ephemeral: true);
    }

    /**
     * Handle the select interaction.
     */
    protected function handleSelect(Interaction $interaction, ?string $type = 'Default'): void
    {
        $type = ucfirst($type);

        $this
            ->message()
            ->title("{$type} Select")
            ->codeField('Selected Values', var_export($interaction->data->values, true))
            ->reply($interaction, ephemeral: true);
    }

    /**
     * Show the example modal.
     */
    protected function showModal(Interaction $interaction): void
    {
        $this
            ->modal('Send a Message')
            ->text('Title', placeholder: 'Enter a title...', minLength: 2, maxLength: 32, required: true)
            ->paragraph('Content', placeholder: 'Enter a message...', minLength: 5, maxLength: 256, required: true)
            ->submit(fn (Interaction $interaction, Collection $components) => $this->handleModal($interaction, $components))
            ->show($interaction);
    }

    /**
     * Handle the modal interaction.
     */
    protected function handleModal(Interaction $interaction, ?Collection $components): void
    {
        $title = $components->get('custom_id', 'title')->value;
        $content = $components->get('custom_id', 'content')->value;

        $this
            ->message()
            ->title($title)
            ->content($content)
            ->field('Author', $interaction->user->__toString())
            ->thumbnail($interaction->user->avatar)
            ->timestamp()
            ->reply($interaction, ephemeral: true);
    }
}
