@if (session('succes'))
    <div role="status" class="mb-6 flex items-start gap-3 rounded-md border border-bleu-100 bg-bleu-50 px-4 py-3 text-sm text-bleu-900">
        <x-icone nom="coche" class="mt-0.5 h-4 w-4 text-bleu-600" /> {{ session('succes') }}
    </div>
@endif
@if (session('erreur'))
    <div role="alert" class="mb-6 flex items-start gap-3 rounded-md border border-alerte/20 bg-alerte-50 px-4 py-3 text-sm text-alerte">
        <x-icone nom="info" class="mt-0.5 h-4 w-4" /> {{ session('erreur') }}
    </div>
@endif
