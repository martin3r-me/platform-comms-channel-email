<?php

namespace Platform\Comms\ChannelEmail\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Platform\Comms\Contracts\ChannelProviderInterface;
use Platform\Comms\ChannelEmail\Models\CommsChannelEmailAccount;

class EmailChannelProvider implements ChannelProviderInterface
{
    public function getType(): string
    {
        return 'email';
    }

    /**
     * Erwartet mindestens: address (oder email) und team_id.
     * Optional: user_id (für persönliche Konten), name, meta, is_default.
     */
    public function createChannel(array $data): string
    {
        $address = $data['address'] ?? $data['email'] ?? null;
        $teamId = $data['team_id'] ?? Auth::user()?->currentTeam?->id;

        if (!$address) {
            throw new \InvalidArgumentException('address (oder email) ist erforderlich.');
        }

        if (!$teamId) {
            throw new \InvalidArgumentException('team_id ist erforderlich.');
        }

        $userId = $data['user_id'] ?? null;
        $createdBy = $data['created_by_user_id'] ?? Auth::id();
        $ownership = $data['ownership_type'] ?? ($userId ? 'user' : 'team');

        $account = CommsChannelEmailAccount::create([
            'address'            => $address,
            'name'               => $data['name'] ?? null,
            'inbound_token'      => $data['inbound_token'] ?? Str::random(32),
            'team_id'            => $teamId,
            'user_id'            => $ownership === 'user' ? $userId : null,
            'created_by_user_id' => $createdBy,
            'ownership_type'     => $ownership,
            'meta'               => $data['meta'] ?? [],
            'is_default'         => $data['is_default'] ?? false,
        ]);

        return 'email:' . $account->id;
    }
}

