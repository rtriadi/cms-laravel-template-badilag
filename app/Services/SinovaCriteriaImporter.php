<?php

namespace App\Services;

use App\Models\CriteriaGroup;
use App\Models\CriteriaItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SinovaCriteriaImporter
{
    /**
     * Preview what would be added, updated, or deactivated by importing a versioned JSON payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     juknis_version: string,
     *     added_groups: list<string>,
     *     added_items: list<string>,
     *     updated_items: list<string>,
     *     removed_items: list<string>
     * }
     */
    public function previewDiff(array $payload): array
    {
        $this->validatePayload($payload);

        $version = (string) $payload['juknis_version'];
        $existingGroupCodes = CriteriaGroup::query()
            ->where('juknis_version', $version)
            ->pluck('code')
            ->all();

        $existingItems = CriteriaItem::query()
            ->whereHas('group', fn ($q) => $q->where('juknis_version', $version))
            ->pluck('name', 'code')
            ->all();

        $addedGroups = [];
        $addedItems = [];
        $updatedItems = [];
        $incomingItemCodes = [];

        foreach ($payload['groups'] as $groupData) {
            $groupCode = (string) $groupData['code'];

            if (! in_array($groupCode, $existingGroupCodes, true)) {
                $addedGroups[] = $groupCode;
            }

            foreach ($groupData['items'] ?? [] as $itemData) {
                $itemCode = (string) $itemData['code'];
                $incomingItemCodes[] = $itemCode;

                if (! array_key_exists($itemCode, $existingItems)) {
                    $addedItems[] = $itemCode;
                } elseif ($existingItems[$itemCode] !== (string) $itemData['name']) {
                    $updatedItems[] = $itemCode;
                }
            }
        }

        $removedItems = array_values(array_diff(array_keys($existingItems), $incomingItemCodes));

        return [
            'juknis_version' => $version,
            'added_groups' => $addedGroups,
            'added_items' => $addedItems,
            'updated_items' => $updatedItems,
            'removed_items' => $removedItems,
        ];
    }

    /**
     * Import a versioned SINOVA criteria JSON payload, preserving existing linked pages and manual URLs.
     *
     * @param  array<string, mixed>  $payload
     * @return array{groups_synced: int, items_synced: int}
     */
    public function import(array $payload): array
    {
        $this->validatePayload($payload);

        $version = (string) $payload['juknis_version'];
        $groupsSynced = 0;
        $itemsSynced = 0;

        DB::transaction(function () use ($payload, $version, &$groupsSynced, &$itemsSynced): void {
            foreach ($payload['groups'] as $groupIndex => $groupData) {
                $group = CriteriaGroup::query()->updateOrCreate(
                    [
                        'juknis_version' => $version,
                        'code' => (string) $groupData['code'],
                    ],
                    [
                        'aspect' => (string) ($groupData['aspect'] ?? 'konten'),
                        'name' => (string) $groupData['name'],
                        'weight' => (float) ($groupData['weight'] ?? 0),
                        'sort_order' => (int) ($groupData['sort_order'] ?? ($groupIndex + 1)),
                    ],
                );
                $groupsSynced++;

                foreach ($groupData['items'] ?? [] as $itemIndex => $itemData) {
                    $item = CriteriaItem::query()->firstOrNew([
                        'code' => (string) $itemData['code'],
                    ]);

                    $item->criteria_group_id = $group->id;
                    $item->name = (string) $itemData['name'];
                    $item->description = $itemData['description'] ?? null;
                    $item->tingkat_scope = (string) ($itemData['tingkat_scope'] ?? 'all');
                    $item->item_type = (string) ($itemData['item_type'] ?? 'link');
                    $item->default_route = $itemData['default_route'] ?? null;
                    $item->sort_order = (int) ($itemData['sort_order'] ?? ($itemIndex + 1));
                    $item->save();

                    $itemsSynced++;
                }
            }
        });

        return [
            'groups_synced' => $groupsSynced,
            'items_synced' => $itemsSynced,
        ];
    }

    /**
     * Export the current criteria structure into versioned JSON format.
     *
     * @return array<string, mixed>
     */
    public function export(string $version = '2026'): array
    {
        $groups = CriteriaGroup::query()
            ->with('items')
            ->where('juknis_version', $version)
            ->orderBy('sort_order')
            ->get();

        return [
            'juknis_version' => $version,
            'exported_at' => now()->toIso8601String(),
            'groups' => $groups->map(fn (CriteriaGroup $group): array => [
                'code' => $group->code,
                'name' => $group->name,
                'aspect' => $group->aspect,
                'weight' => $group->weight,
                'sort_order' => $group->sort_order,
                'items' => $group->items->map(fn (CriteriaItem $item): array => [
                    'code' => $item->code,
                    'name' => $item->name,
                    'description' => $item->description,
                    'tingkat_scope' => $item->tingkat_scope,
                    'item_type' => $item->item_type,
                    'default_route' => $item->default_route,
                    'sort_order' => $item->sort_order,
                ])->all(),
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validatePayload(array $payload): void
    {
        if (empty($payload['juknis_version']) || ! is_array($payload['groups'] ?? null)) {
            throw new InvalidArgumentException('Format JSON kriteria SINOVA tidak valid: field juknis_version dan groups wajib tersedia.');
        }
    }
}
