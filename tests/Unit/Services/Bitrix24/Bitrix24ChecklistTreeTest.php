<?php

namespace Tests\Unit\Services\Bitrix24;

use App\Services\Bitrix24\Bitrix24ChecklistTree;
use PHPUnit\Framework\TestCase;

class Bitrix24ChecklistTreeTest extends TestCase
{
    public function test_walks_children_after_parent_by_sort_index(): void
    {
        $ordered = Bitrix24ChecklistTree::inDisplayOrder([
            ['id' => 2, 'parent_id' => 0, 'sort_index' => 20, 'title' => 'второй', 'is_complete' => true, 'toggled_at' => null],
            ['id' => 1, 'parent_id' => 0, 'sort_index' => 10, 'title' => 'первый', 'is_complete' => true, 'toggled_at' => null],
            ['id' => 3, 'parent_id' => 1, 'sort_index' => 20, 'title' => 'вложенный 2', 'is_complete' => true, 'toggled_at' => null],
            ['id' => 4, 'parent_id' => 1, 'sort_index' => 10, 'title' => 'вложенный 1', 'is_complete' => true, 'toggled_at' => null],
        ]);

        $this->assertSame(
            ['первый', 'вложенный 1', 'вложенный 2', 'второй'],
            array_column($ordered, 'title'),
        );
        $this->assertSame([0, 1, 1, 0], array_column($ordered, 'depth'));
    }

    public function test_incomplete_wrapper_does_not_indent_children(): void
    {
        $ordered = Bitrix24ChecklistTree::inDisplayOrder([
            ['id' => 1, 'parent_id' => 0, 'sort_index' => 10, 'title' => 'Чек-лист 1', 'is_complete' => false, 'toggled_at' => null],
            ['id' => 2, 'parent_id' => 1, 'sort_index' => 10, 'title' => 'первый', 'is_complete' => true, 'toggled_at' => null],
            ['id' => 3, 'parent_id' => 2, 'sort_index' => 10, 'title' => 'вложенный', 'is_complete' => true, 'toggled_at' => null],
        ]);

        $this->assertSame(['Чек-лист 1', 'первый', 'вложенный'], array_column($ordered, 'title'));
        $this->assertSame([0, 0, 1], array_column($ordered, 'depth'));
        $this->assertFalse($ordered[0]['is_complete']);
    }
}
