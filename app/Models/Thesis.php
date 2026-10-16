<?php

namespace App\Models;

use Database\Factories\ThesisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Thesis extends Model
{
    /** @use HasFactory<ThesisFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'supervisor_id',
        'examiner_id',
        'title',
        'abstract',
        'status',
        'examiner_access_expires_at',
        'removal_reason',
        'grade',
        'grade_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'examiner_access_expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Thesis $thesis) {
            if (empty($thesis->slug)) {
                $thesis->slug = Str::slug($thesis->title)
                    .'-'.Str::lower(Str::random(8));
            }
        });
    }

    /**
     * Get the student who owns the thesis.
     */
    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the supervisor assigned to the thesis.
     */
    /** @return BelongsTo<User, $this> */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the examiner assigned to the thesis.
     */
    /** @return BelongsTo<User, $this> */
    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_id');
    }

    /** @return HasOne<ThesisDocument, $this> */
    public function document(): HasOne
    {
        return $this->hasOne(ThesisDocument::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
