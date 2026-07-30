{{-- Placeholder rows shown via wire:loading while a table's own request
     (search/filter/sort/paginate) is in flight — sits as a second <tbody>
     swapped in for the real one, so <thead> stays put and doesn't flicker. --}}
@props(['columns' => 6, 'rows' => 6])

@for ($r = 0; $r < $rows; $r++)
    <tr>
        @for ($c = 0; $c < $columns; $c++)
            <td class="px-4 py-4">
                <div
                    class="h-3.5 animate-pulse rounded-full bg-gray-200 dark:bg-white/10"
                    style="width: {{ [86, 62, 74, 46, 80, 58][($r + $c) % 6] }}%"
                ></div>
            </td>
        @endfor
    </tr>
@endfor
