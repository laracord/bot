<?php

namespace App\Commands;

use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Illuminate\Support\Str;
use Laracord\Commands\Command;

class RockPaperScissors extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'rps';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'A classic game of rock, paper, scissors.';

    /**
     * The choices for the game.
     */
    protected array $choices = [
        'rock' => '🪨',
        'paper' => '📝',
        'scissors' => '✂️',
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
        if ($message->channel_id !== '1218368357829574666') {
            return;
        }

        $message->reply($this->game());
    }

    /**
     * Start a game of rock, paper, scissors.
     */
    public function game(): MessageBuilder
    {
        $embed = $this->message('Choose wisely...')->warning();

        foreach ($this->choices as $choice => $emoji) {
            $embed = $embed->button(
                Str::title($choice),
                fn (Interaction $interaction) => $this->play($choice, $interaction),
                style: 'secondary',
                emoji: $emoji,
            );
        }

        return $embed->build();
    }

    /**
     * Play a round of rock, paper, scissors.
     */
    public function play(string $choice, Interaction $interaction)
    {
        $result = array_rand($this->choices);

        $outcome = match ($choice) {
            'rock' => match ($result) {
                'rock' => 'tie',
                'paper' => 'lose',
                'scissors' => 'win',
            },
            'paper' => match ($result) {
                'rock' => 'win',
                'paper' => 'tie',
                'scissors' => 'lose',
            },
            'scissors' => match ($result) {
                'rock' => 'lose',
                'paper' => 'win',
                'scissors' => 'tie',
            },
        };

        $embed = $this
            ->message()
            ->field('Player', $interaction->user->__toString())
            ->timestamp();

        $embed = match ($outcome) {
            'win' => $embed->success(),
            'lose' => $embed->error(),
            'tie' => $embed->info(),
        };

        $embed = $embed->content("You chose **{$this->choice($choice)}** and I chose **{$this->choice($result)}**. You **{$outcome}**!");

        $interaction
            ->updateMessage($embed->build())
            ->then(fn () => $interaction->message->reply($this->game()));
    }

    /**
     * Format the choice.
     */
    public function choice(string $value): string
    {
        return Str::of($value)
            ->title()
            ->start("{$this->choices[$value]} ")
            ->toString();
    }

    /**
     * {@inheritDoc}
     */
    public function message($content = '')
    {
        return parent::message($content)->title('Rock, Paper, Scissors');
    }
}
