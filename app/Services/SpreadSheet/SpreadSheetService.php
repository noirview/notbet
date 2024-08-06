<?php

namespace App\Services\SpreadSheet;

use App\Enums\Bookmaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Revolution\Google\Sheets\Facades\Sheets;

class SpreadSheetService
{
    private function printCell(string $id = null, string $text = null): array
    {
        if (is_null($id) && is_null($text)) {
            return ['', '', ''];
        } else {
            return [$id, $text, ''];
        }
    }

    private function printRow(...$cells): array
    {
        if (count($cells) === 0) {
            return [];
        }

        $row = [];
        foreach ($cells as $cell) {
            $row = array_merge($row, $cell);
        }

        return $row;
    }

    public function print(Collection $entities, string $groupField): void
    {
        $rows = [];

        $rows[] = $this->printRow(
            $this->printCell('', 'Marathonbet'),
            $this->printCell('', 'Maxline'),
        );

        $grouped = collect();
        $singular = collect();

        $groupedEntities = $entities->groupBy($groupField);
        foreach ($groupedEntities as $group) {
            if ($group->count() > 1) {
                $grouped = $grouped->merge($group);
            } else {
                $singular = $singular->merge($group);
            }
        }

        $rows[] = $this->printRow();

        foreach ($grouped->groupBy($groupField) as $group) {
            $mbBookmakerEntities = $group->where('bookmaker', Bookmaker::fromValue(Bookmaker::MARATHONBET))->values();
            $mlBookmakerEntities = $group->where('bookmaker', Bookmaker::fromValue(Bookmaker::MAXLINE))->values();

            $countMbBookmakerEntities = $mbBookmakerEntities->count();
            $countMlBookmakerEntities = $mlBookmakerEntities->count();

            $maxCount = max([
                $countMbBookmakerEntities,
                $countMlBookmakerEntities,
            ]);

            for ($index = 0; $index < $maxCount; $index++) {
                $mbBookmakerEntity = $mbBookmakerEntities->get($index);
                $mlBookmakerEntity = $mlBookmakerEntities->get($index);

                $rows[] = $this->printRow(
                    $mbBookmakerEntity ? $this->printCell(
                        $mbBookmakerEntity->{$groupField},
                        $mbBookmakerEntity->name
                    ) : $this->printCell(),
                    $mlBookmakerEntity ? $this->printCell(
                        $mlBookmakerEntity->{$groupField},
                        $mlBookmakerEntity->name
                    ) : $this->printCell(),
                );
            }

            $rows[] = $this->printRow();
        }

        $rows[] = $this->printRow();

        $mbBookmakerEntities = $singular->where('bookmaker', Bookmaker::fromValue(Bookmaker::MARATHONBET))->values();
        $mlBookmakerEntities = $singular->where('bookmaker', Bookmaker::fromValue(Bookmaker::MAXLINE))->values();

        $countMbBookmakerEntities = $mbBookmakerEntities->count();
        $countMlBookmakerEntities = $mlBookmakerEntities->count();

        $maxCount = max([
            $countMbBookmakerEntities,
            $countMlBookmakerEntities,
        ]);

        for ($index = 0; $index < $maxCount; $index++) {
            $mbBookmakerEntity = $mbBookmakerEntities->get($index);
            $mlBookmakerEntity = $mlBookmakerEntities->get($index);

            $rows[] = $this->printRow(
                $mbBookmakerEntity ? $this->printCell(
                    $mbBookmakerEntity->{$groupField},
                    $mbBookmakerEntity->name
                ) : $this->printCell(),
                $mlBookmakerEntity ? $this->printCell(
                    $mlBookmakerEntity->{$groupField},
                    $mlBookmakerEntity->name
                ) : $this->printCell(),
            );
        }

        Sheets::spreadsheet(config('google.config.spreadsheet_id'))
            ->sheet('Лист1')
            ->update($rows);
    }

    public function link($linkService): void
    {
        $rows = Sheets::spreadsheet(config('google.config.spreadsheet_id'))
            ->sheet('Лист1')
            ->get();

        $i = 2;

        $bookmakerEntityIds = [];
        do {
            if (count($rows[$i]) != 0) {
                $row = $rows[$i];

                for ($index = 0; $index < count($row) / 3; $index++) {
                    $bookmakerEntityId = $row[$index * 3];
                    if ($bookmakerEntityId !== '') {
                        $bookmakerEntityIds[] = $bookmakerEntityId;
                    }
                }
            }

            if (!Arr::has($rows, $i + 1) || !Arr::has($rows, $i + 2) || count($rows[$i + 2]) == 0) {
                $bookmakerEntityIdsCollection = collect($bookmakerEntityIds);
                $linkService->link(
                    $bookmakerEntityIdsCollection->first(),
                    $bookmakerEntityIdsCollection->slice(1)
                );
                break;
            }

            if (count($rows[$i]) == 0) {
                $bookmakerEntityIdsCollection = collect($bookmakerEntityIds);
                $linkService->link(
                    $bookmakerEntityIdsCollection->first(),
                    $bookmakerEntityIdsCollection->slice(1)
                );

                $bookmakerEntityIds = [];

                $i++;
                continue;
            }

            $i++;
        } while ($i < count($rows));
    }
}
