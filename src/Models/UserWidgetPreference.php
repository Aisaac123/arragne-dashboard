<?php

namespace Shreejan\DashArrange\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * User Widget Preference Model.
 *
 * Stores user-specific widget preferences including visibility and order.
 */
class UserWidgetPreference extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'widget_name',
        'order',
        'show_widget',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'show_widget' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        $userModel = config('dash-arrange.user_model');

        return $this->belongsTo($userModel);
    }
}
