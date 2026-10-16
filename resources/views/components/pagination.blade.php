@if($paginator->hasPages())
    <div class="flex flex-col gap-3 border-t border-slate-100 px-6 py-4 text-sm sm:flex-row sm:items-center sm:justify-between">
        <span class="text-slate-500">Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data</span>
        <div class="flex gap-2">
            @if($paginator->onFirstPage())
                <span class="rounded-lg border border-slate-100 px-3 py-2 text-slate-300">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="rounded-lg border border-slate-200 px-3 py-2 text-slate-600 hover:bg-slate-50">Sebelumnya</a>
            @endif
            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="rounded-lg border border-slate-200 px-3 py-2 text-slate-600 hover:bg-slate-50">Berikutnya</a>
            @else
                <span class="rounded-lg border border-slate-100 px-3 py-2 text-slate-300">Berikutnya</span>
            @endif
        </div>
    </div>
@endif
