<?php

namespace App\Services;

use App\Models\Release;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laracord\Services\Service;

class CheckRelease extends Service
{
    /**
     * The repositories.
     */
    protected array $repositories = [
        'laracord/laracord' => 'Laracord',
        'laracord/framework' => 'Framework',
    ];

    /**
     * The current releases.
     */
    protected array $current = [];

    /**
     * The API token.
     */
    protected string $token = '';

    /**
     * The service interval.
     */
    protected int $interval = 60;

    /**
     * The channel ID.
     */
    protected string $channel = '1209880528929431623';

    /**
     * Handle the service.
     */
    public function handle(): void
    {
        $latest = $this->releases()->reject(
            fn ($release, $repository) => $this->current($repository) === $release['id']
        );

        $latest->each(function ($release, $repository) {
            Release::updateOrCreate(['repository' => $repository], [
                'latest' => $release['id'],
            ]);

            $this->broadcast($repository, $release);

            $this->current[$repository] = $release['id'];
        });
    }

    /**
     * Broadcast the release.
     */
    protected function broadcast(string $repository, array $release): void
    {
        $name = $this->repositories[$repository];

        $body = Str::of($release['body'])
            ->before('**Full Changelog**:')
            ->replace('## Change log', '')
            ->replace("\r\n\r\n", "\r\n")
            ->trim()
            ->toString();

        $this->message()
            ->title("{$name} {$release['tag_name']}")
            ->content($body)
            ->button('View Release', $release['html_url'])
            ->timestamp($release['published_at'])
            ->send($this->channel);
    }

    /**
     * Retrieve the latest releases from GitHub.
     */
    protected function releases(): Collection
    {
        return collect($this->endpoints())->mapWithKeys(fn ($endpoint, $repository) => [
            $repository => $this->client()->get($endpoint)->json(),
        ])->map(fn ($release) => array_pop($release));
    }

    /**
     * Retrieve the current releases.
     */
    protected function current($key = null): mixed
    {
        if (! $this->current) {
            $this->current = Release::all()->pluck('latest', 'repository')->all();
        }

        return $key ? ($this->current[$key] ?? null) : $this->current;
    }

    /**
     * Retrieve the HTTP client.
     */
    protected function client()
    {
        return Http::withToken($this->token())
            ->accept('application/vnd.github+json')
            ->withQueryParameters(['per_page' => 1]);
    }

    /**
     * Retrieve the API token.
     */
    protected function token(): string
    {
        if ($this->token) {
            return $this->token;
        }

        return $this->token = env('GITHUB_TOKEN', '');
    }

    /**
     * Retrieve the repository endpoints.
     */
    protected function endpoints(): array
    {
        return collect($this->repositories)->keys()->mapWithKeys(fn ($repository) => [
            $repository => "https://api.github.com/repos/{$repository}/releases",
        ])->all();
    }
}
