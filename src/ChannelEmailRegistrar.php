<?php

namespace Platform\Comms\ChannelEmail;

use Platform\Comms\Registry\ChannelRegistry;
use Platform\Comms\ChannelEmail\Models\CommsChannelEmailAccount;

class ChannelEmailRegistrar
{
    public static function registerChannels(): void
    {
        // Registriere **alle** Accounts – Comms filtert später auf User/Team
        CommsChannelEmailAccount::query()
            ->get()
            ->each(function (CommsChannelEmailAccount $account) {
                ChannelRegistry::register([
                    'id'        => 'email:' . $account->id,   // eindeutig, lesbar
                    'type'      => 'email',
                    'label'     => $account->label ?? $account->address,
                    'component' => \Platform\Comms\ChannelEmail\Http\Livewire\Accounts\Index::class,
                    'group'     => 'E-Mail',
                    'team_id'   => $account->team_id,
                    'user_id'   => $account->user_id, // darf null sein
                    'payload'   => [
                        'account_id' => $account->id,
                    ],
                ]);
            });
    }
}