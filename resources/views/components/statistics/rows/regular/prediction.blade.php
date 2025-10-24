@props(['params'])

{{-- Установка высоты ячейки в маленькое значение - хак
https://stackoverflow.com/questions/3542090/how-to-make-div-fill-td-height--}}
<x-data.table-cell {{ $attributes }} class="!p-0 h-1">
    <div class="flex flex-col justify-evenly h-full">
        <div class="flex items-center grow px-2.5 py-2">
            <span>~1871</span>
        </div>
        <hr style="color: #d0ddee">
        <div class="flex items-center grow px-2.5 py-2">
            -
        </div>
    </div>
</x-data.table-cell>