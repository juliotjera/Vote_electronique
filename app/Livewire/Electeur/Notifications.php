<?php

namespace App\Livewire\Electeur;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Notifications extends Component
{
    public function mount(): void
    {
        // ouvrir la page vaut lecture
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        return view('livewire.electeur.notifications', [
            'notifications' => auth()->user()->notifications()->latest()->limit(50)->get(),
        ])->title(__('Notifications'));
    }
}
