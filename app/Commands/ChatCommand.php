<?php

namespace App\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laracord\Commands\Command;
use OpenAI;

class ChatCommand extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'chat';

    /**
     * The command description.
     *
     * @var string|null
     */
    protected $description = 'Chat with the bot.';

    /**
     * The command usage.
     *
     * @var string
     */
    protected $usage = '<message>';

    /**
     * The OpenAI client instance.
     *
     * @var \OpenAI\Client
     */
    protected $client;

    /**
     * The OpenAI API key.
     */
    protected string $apiKey = '';

    /**
     * The OpenAI system prompt.
     */
    protected string $prompt = 'You only reply with 1-2 sentences at a time as if responding to a chat message.';

    /**
     * Execute the Discord command.
     *
     * @param  \Discord\Parts\Channel\Message  $message
     * @param  array  $args
     * @return mixed
     */
    public function handle($message, $args)
    {
        if (! $this->apiKey()) {
            $this->console()->warn('The OpenAI API key is not set.');

            return $this
                ->message('This command is not available.')
                ->title('Chat')
                ->error()
                ->send($message);
        }

        $input = trim(
            implode(' ', $args ?? [])
        );

        if (! $input) {
            return $this
                ->message('You must provide a message.')
                ->title('Chat')
                ->error()
                ->send($message);
        }

        $message->channel->broadcastTyping()->done(function () use ($message, $input) {
            $key = "{$message->channel->id}.chat.responses";

            $input = $this->resolveMentions($message, Str::limit($input, 384));

            $messages = cache()->get($key, [['role' => 'system', 'content' => $this->prompt]]);
            $messages[] = ['role' => 'user', 'content' => $input];

            $result = $this->client()->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
            ]);

            $response = $result->choices[0]->message->content;

            $messages[] = ['role' => 'assistant', 'content' => $response];

            cache()->put($key, $messages, now()->addMinute());

            return $this
                ->message($response)
                ->send($message);
        });
    }

    /**
     * Retrieve the OpenAPI client instance.
     *
     * @return \OpenAI\Client
     */
    protected function client()
    {
        if ($this->client) {
            return $this->client;
        }

        return $this->client = OpenAI::client($this->apiKey());
    }

    /**
     * Retrieve the OpenAPI API key.
     *
     * @return string
     */
    protected function apiKey()
    {
        if ($this->apiKey) {
            return $this->apiKey;
        }

        return $this->apiKey = env('OPENAI_API_KEY', $this->apiKey);
    }

    /**
     * Resolve the mentioned users.
     *
     * @param  \Discord\Parts\Channel\Message  $message
     * @param  string  $question
     * @return string
     */
    protected function resolveMentions($message, $question)
    {
        return preg_replace_callback('/<@&?([0-9]+)>/', function ($matches) use ($message) {
            $user = $message->channel->guild->members->get('id', $matches[1]);

            if ($user) {
                $user = Arr::get($user->getRawAttributes(), 'user', []) ?? $user;
            }

            return $user ? ($user->global_name ?? $user->username) : $matches[0];
        }, $question);
    }
}
