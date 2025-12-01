<?php

namespace Shreejan\CustomizeDashboardWidget\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserWidgetPreference extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'widget_name',
        'order',
        'show_widget',
    ];

    protected $casts = [
        'show_widget' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        $userModel = config('customize-dashboard-widget.user_model');

        return $this->belongsTo($userModel);
    }
}