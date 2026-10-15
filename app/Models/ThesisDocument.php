<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThesisDocument extends Model
{
    use HasFactory, SoftDeletes;

    // Define document type constants for safe application-level usage
    public const TYPE_PROPOSAL = 'proposal'; //[cite: 2]
    public const TYPE_PROGRESS_REPORT = 'progress_report'; //[cite: 2]
    public const TYPE_FINAL_THESIS = 'final_thesis'; //[cite: 2]

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'thesis_id',
        'uploaded_by',
        'document_type',
        'original_name',
        'storage_path',
        'mime_type',
        'file_size',
        'file_hash',
        'similarity_score',
    ];

    /**
     * Get the thesis that owns the document.
     */
    public function thesis(): BelongsTo
    {
        return $this->belongsTo(Thesis::class);
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
