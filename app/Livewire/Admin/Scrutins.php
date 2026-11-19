<?php

namespace App\Livewire\Admin;

use App\Models\Scrutin;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Scrutins')]
class Scrutins extends Component
{
    public function render()
    {
        return view('livewire.admin.scrutins', [
            'scrutins' => Scrutin::with(['classes', 'groupes'])->withCount(['candidatures' => fn ($q) => $q->validees()])
                ->latest('ouverture_at')->get(),
        ]);
    }
}
