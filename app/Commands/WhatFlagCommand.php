<?php

namespace App\Commands;

use Carbon\Carbon;
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
     * The command interaction routes.
     */
    public function interactions(): array
    {
        return [
            'guess:{selected}:{correct}:{options}' => fn (Interaction $interaction, string $selected, string $answer, string $options) => $this->guess($interaction, $selected, $answer, $options),
        ];
    }

    /**
     * Build a game embed.
     */
    public function game(): MessageBuilder
    {
        $answer = $this->countries()->random();

        $options = $this->countries()
            ->where('name', '!=', $answer['name'])
            ->random(3)
            ->push($answer)
            ->shuffle()
            ->all();

        $embed = $this
            ->message("Which flag belongs to **{$answer['name']}**?")
            ->warning();

        $answers = collect($options)->map(fn ($answer) => $answer['code'])->implode(',');

        foreach ($options as $index => $option) {
            $embed = $embed->button(
                'Option '.$index + 1,
                route: "guess:{$option['code']}:{$answer['code']}:{$answers}",
                emoji: $option['emoji'],
                style: 'secondary'
            );
        }

        return $embed->build();
    }

    /**
     * Handle the guess interaction.
     */
    public function guess(Interaction $interaction, string $selected, string $answer, string $options): void
    {
        $selected === $answer
            ? $this->win($interaction, $selected, $options)
            : $this->lose($interaction, $selected, $answer, $options);
    }

    /**
     * The win action.
     */
    public function win(Interaction $interaction, string $answer, string $options): void
    {
        $timestamp = $this->elapsedTime($interaction->message->timestamp);

        $answer = $this->countries()->get($answer);

        $options = $this->getCountries($options)
            ->map(fn ($country) => $country['emoji'])
            ->implode(' ');

        $embed = $this
            ->message("**You win!**\nThe correct flag was **{$answer['emoji']} {$answer['name']}**.")
            ->fields([
                'Guesser' => $interaction->user->__toString(),
                'Options' => $options,
                'Time elapsed' => $timestamp,
            ])
            ->timestamp()
            ->success();

        $interaction
            ->acknowledge()
            ->then(fn () => $interaction->message->edit($embed->build()))
            ->then(fn () => $interaction->message->reply($this->game()));
    }

    /**
     * The lose action.
     */
    public function lose(Interaction $interaction, string $selected, string $answer, string $options): void
    {
        $timestamp = $this->elapsedTime($interaction->message->timestamp);

        $selected = $this->countries()->get($selected);
        $answer = $this->countries()->get($answer);

        $options = $this->getCountries($options)
            ->map(fn ($country) => $country['emoji'])
            ->implode(' ');

        $embed = $this
            ->message("**You lose!**\nYou picked **{$selected['emoji']} {$selected['name']}** but the correct flag was **{$answer['emoji']} {$answer['name']}**.")
            ->fields([
                'Guesser' => $interaction->user->__toString(),
                'Options' => $options,
                'Time elapsed' => $timestamp,
            ])
            ->timestamp()
            ->error();

        $interaction
            ->acknowledge()
            ->then(fn () => $interaction->message->edit($embed->build()))
            ->then(fn () => $interaction->channel->sendMessage($this->game()));
    }

    /**
     * Calculate the elapsed time.
     */
    public function elapsedTime(Carbon $timestamp): string
    {
        $timestamp = now()->diffInMilliseconds($timestamp) / 1000;

        $timestamp = $timestamp > 60
            ? number_format($timestamp / 60, 2).' minutes'
            : number_format($timestamp, 2).' seconds';

        return $timestamp;
    }

    /**
     * Retrieve the country data for a set of codes.
     */
    public function getCountries(string|array $codes): Collection
    {
        $codes = is_array($codes) ? $codes : explode(',', $codes);

        return collect($codes)
            ->map(fn ($code) => $this->countries()->get($code));
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

        return $this->countries = collect(File::json($countries))->keyBy('code');
    }

    /**
     * {@inheritDoc}
     */
    public function message($content = '')
    {
        return parent::message($content)->title('What Flag?');
    }
}
