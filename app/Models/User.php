<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'lastlogin',
        'photo_url',
        'nin_no',
        'dateOfBirth',
        'phone_number',
        'agree_to_terms',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function reviews()
    {
        return $this->hasMany(UserReview::class);
    }
    // Add 'rating' to the array or JSON form of the model
    protected $appends = ['rating'];
    // Accessor for the average rating
    public function getRatingAttribute()
    {
        return $this->reviews()->average('rating');
    }

    public function vendors()
    {
        return $this->hasOne(UserVendor::class, 'user_id');
    }

    protected static function booted()
    {
        static::creating(function ($car) {
            $car->slug = static::uniqueSlug($car->name);
        });
    }

    public static function uniqueSlug($string)
    {
        $baseSlug = Str::slug($string, '-');
        if (static::where('slug', $baseSlug)->doesntExist()) {
            return $baseSlug;
        }

        $counter = 1;
        // Limiting the counter to prevent infinite loops
        while ($counter < 1000) {
            $slug = "{$baseSlug}-{$counter}";
            if (static::where('slug', $slug)->doesntExist()) {
                return $slug;
            }
            $counter++;
        }

        // Fallback if reached 1000 iterations (should ideally never happen)
        return "{$baseSlug}-" . uniqid();
    }

    public function providers()
    {
        return $this->hasMany(ThirdPartyAuthProvider::class, 'user_id', 'id');
    }

    public function setEmailAttribute($value)
    {
        if (empty($value)) { // will check for empty string
            $this->attributes['email'] = null;
        } else {
            $this->attributes['email'] = $value;
        }
    }

    public function setPhoneNumberAttribute($value)
    {
        if (empty($value)) { // will check for empty string
            $this->attributes['phone_number'] = null;
        } else {
            $this->attributes['phone_number'] = $value;
        }
    }

}