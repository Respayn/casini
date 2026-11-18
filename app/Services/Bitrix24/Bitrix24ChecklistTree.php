<?php

namespace App\Services\Bitrix24;

/**
 * Порядок и вложенность пунктов чек-листа Битрикс24: как в задаче, а не по дате галочки.
 */
final class Bitrix24ChecklistTree
{
    /**
     * @param  list<array{id: int, parent_id: int, sort_index: int, title: string, is_complete: bool, toggled_at: mixed}>  $items
     * @return list<array{id: int, parent_id: int, sort_index: int, title: string, is_complete: bool, toggled_at: mixed, depth: int}>
     */
    public static function inDisplayOrder(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $ids = [];
        foreach ($items as $item) {
            $ids[$item['id']] = true;
        }

        $roots = [];
        $children = [];

        foreach ($items as $item) {
            $parentId = $item['parent_id'];
            if ($parentId === 0 || ! isset($ids[$parentId])) {
                $roots[] = $item;
            } else {
                $children[$parentId][] = $item;
            }
        }

        $sort = static function (array &$group): void {
            usort(
                $group,
                static fn (array $left, array $right): int => $left['sort_index'] <=> $right['sort_index']
                    ?: $left['id'] <=> $right['id']
            );
        };

        $sort($roots);
        foreach ($children as &$group) {
            $sort($group);
        }
        unset($group);

        $ordered = [];
        $walk = function (array $nodes, int $depth) use (&$walk, &$ordered, $children): void {
            if ($depth > 10) {
                return;
            }

            foreach ($nodes as $node) {
                $ordered[] = $node + ['depth' => $depth];
                $nextDepth = $node['is_complete'] ? $depth + 1 : $depth;
                $walk($children[$node['id']] ?? [], $nextDepth);
            }
        };
        $walk($roots, 0);

        return $ordered;
    }
}
