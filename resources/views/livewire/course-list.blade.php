<div class="p-6">
    <h1 class="text-2xl font-bold mb-4 text-indigo-700">Listado de Cursos (Postgres)</h1>

    <div class="mb-4">
        <input
            wire:model.live="searchForm.query"
            type="text"
            placeholder="Buscar por título..."
            class="w-full p-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
    </div>

    <div class="space-y-2">
        @forelse($courses as $course)
            <div class="p-3 bg-gray-50 border rounded shadow-sm">
                <span class="font-bold">{{ $course->title }}</span> - ${{ $course->price }}
            </div>
        @empty
            <p class="text-gray-500 italic">No hay cursos disponibles que coincidan.</p>
        @endforelse
    </div>
</div>
