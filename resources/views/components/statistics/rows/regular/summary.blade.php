@props(['params'])

{{-- Установка высоты ячейки в маленькое значение - хак
https://stackoverflow.com/questions/3542090/how-to-make-div-fill-td-height--}}
<x-data.table-cell {{ $attributes }} class="!p-0 h-1">
    <div class="flex flex-col justify-evenly h-full font-bold">
        <div class="flex grow items-center justify-between px-2.5 py-2 gap-5 bg-[#EBFCF0]">
            <span>1871</span>
            <span>84%</span>
        </div>
        <hr style="color: #d0ddee">
        <div class="flex grow items-center justify-between px-2.5 py-2 bg-table-summary-bg">
            <span>8</span>
            <span></span>
        </div>
    </div>
</x-data.table-cell>