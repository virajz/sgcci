<?php

namespace Database\Seeders;

use App\Models\Segment;
use App\Models\SubSegment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SegmentSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('data/segments.csv');

        if (! is_readable($csvPath)) {
            throw new RuntimeException("Cannot read segments CSV at {$csvPath}");
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Failed to open {$csvPath}");
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                throw new RuntimeException('Segments CSV is empty.');
            }

            $segmentNames = array_map(
                fn (mixed $name) => $this->clean((string) $name),
                $header
            );

            DB::transaction(function () use ($handle, $segmentNames) {
                $segmentIds = [];
                foreach ($segmentNames as $col => $name) {
                    if ($name === '') {
                        $segmentIds[$col] = null;

                        continue;
                    }

                    $segment = Segment::updateOrCreate(
                        ['name' => $name],
                        ['sort_order' => $col, 'is_active' => true],
                    );
                    $segmentIds[$col] = $segment->id;
                }

                $rowOrder = 0;
                while (($row = fgetcsv($handle)) !== false) {
                    foreach ($row as $col => $cell) {
                        $name = $this->clean((string) $cell);
                        $segmentId = $segmentIds[$col] ?? null;

                        if ($name === '' || $segmentId === null) {
                            continue;
                        }

                        SubSegment::updateOrCreate(
                            ['segment_id' => $segmentId, 'name' => $name],
                            ['sort_order' => $rowOrder, 'is_active' => true],
                        );
                    }
                    $rowOrder++;
                }
            });
        } finally {
            fclose($handle);
        }
    }

    private function clean(string $value): string
    {
        $value = str_replace(["\xC2\xA0", "\xE2\x80\x93", "\xE2\x80\x94"], [' ', '-', '-'], $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
