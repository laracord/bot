<?php

namespace App\Commands;

use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Laracord\Commands\Command;

class WhatFlagCommand extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'whatflag';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Guess the country flags!';

    /**
     * The country data.
     */
    protected ?Collection $countries = null;

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
     * The win action.
     */
    public function win(Interaction $interaction, array $country, array $answers): void
    {
        $timestamp = now()->diffInMilliseconds($interaction->message->timestamp);
        $timestamp = number_format($timestamp / 1000, 2);

        $answers = collect($answers)->map(fn ($answer) => $answer['emoji'])->implode(' ');

        $embed = $this
            ->message("**You win!**\nThe correct flag was **{$country['emoji']} {$country['name']}**.")
            ->title('What Flag?')
            ->fields([
                'Guesser' => $interaction->user->__toString(),
                'Options' => $answers,
                'Time elapsed' => "{$timestamp} seconds",
            ])
            ->timestamp(now()->toIso8601String())
            ->success();

        $interaction
            ->acknowledge()
            ->then(fn () => $interaction->message->edit($embed->build()))
            ->then(fn () => $interaction->message->reply($this->game()));
    }

    /**
     * The lose action.
     */
    public function lose(Interaction $interaction, array $answer, array $country, array $answers): void
    {
        $answers = collect($answers)->map(fn ($answer) => $answer['emoji'])->implode(' ');

        $timestamp = now()->diffInMilliseconds($interaction->message->timestamp);
        $timestamp = number_format($timestamp / 1000, 2);

        $embed = $this
            ->message("**You lose!**\nYou picked **{$answer['emoji']} {$answer['name']}** but the correct flag was **{$country['emoji']} {$country['name']}**.")
            ->title('What Flag?')
            ->fields([
                'Guesser' => $interaction->user->__toString(),
                'Options' => $answers,
                'Time elapsed' => "{$timestamp} seconds",
            ])
            ->timestamp(now()->toIso8601String())
            ->error();

        $interaction
            ->acknowledge()
            ->then(fn () => $interaction->message->edit($embed->build()))
            ->then(fn () => $interaction->channel->sendMessage($this->game()));
    }

    /**
     * Build a game embed.
     */
    public function game(): MessageBuilder
    {
        $country = $this->countries()->random();

        $answers = $this->countries()
            ->where('name', '!=', $country['name'])
            ->random(3)
            ->push($country)
            ->shuffle()
            ->all();

        $embed = $this
            ->message("Which flag belongs to **{$country['name']}**?")
            ->title('What Flag?')
            ->warning();

        foreach ($answers as $index => $answer) {
            $embed = $embed->button(
                'Option '.$index + 1,
                fn (Interaction $interaction) => $answer === $country
                    ? $this->win($interaction, $answer, $answers)
                    : $this->lose($interaction, $answer, $country, $answers),
                emoji: $answer['emoji'],
                style: 'secondary'
            );
        }

        return $embed->build();
    }

    /**
     * Retrieve the country data.
     */
    public function countries(): Collection
    {
        if ($this->countries) {
            return $this->countries;
        }

        $countries = database_path('countries.json');

        return $this->countries = collect(File::json($countries));
    }
}
