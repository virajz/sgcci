<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberImport extends Model
{
    protected $fillable = [
        'type',
        'file_path',
        'import_mode',
        'status',
        'total_rows',
        'processed_rows',
        'imported_rows',
        'skipped_rows',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'imported_rows' => 'integer',
            'skipped_rows' => 'integer',
        ];
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }

    public function progressPercent(): int
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return (int) round(($this->processed_rows / $this->total_rows) * 100);
    }
}
