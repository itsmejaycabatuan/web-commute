<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Makes a model write an audit entry whenever it is created, updated or deleted through Eloquent.
 *
 * The entry names the record (subject) and holds only the business attributes that changed,
 * old → new. A model opts in with:
 *
 *     use Audited;
 *     protected function auditCategory(): string   { return Audit::FLEET; }
 *     protected function auditAttributes(): array  { return ['plate_number', 'status']; }
 *
 * and may override auditLabel() / auditDescription(). Keep secrets, blobs and fast-changing
 * columns (passwords, remember tokens, license image data, heartbeat flags) OUT of
 * auditAttributes() — only what is listed is ever stored.
 *
 * Writes that bypass Eloquent (Model::where(...)->update(), DB::table()) fire no model events;
 * those places call App\Support\Audit::log() by hand.
 *
 * To skip an event a model logs by hand instead, declare:
 *     protected static $recordEvents = ['updated', 'deleted'];
 */
trait Audited
{
    use LogsActivity;

    abstract protected function auditCategory(): string;

    /** @return string[] */
    abstract protected function auditAttributes(): array;

    /** Short name of this record as a person would say it, e.g. "vehicle ABC 123". */
    protected function auditLabel(): string
    {
        return strtolower(class_basename($this)).' #'.$this->getKey();
    }

    /** "Created vehicle ABC 123" — no ":" (Spatie treats ":word" as a placeholder). */
    protected function auditDescription(string $event): string
    {
        return ucfirst($event).' '.$this->auditLabel();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName($this->auditCategory())
            ->logOnly($this->auditAttributes())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => $this->auditDescription($event));
    }
}
