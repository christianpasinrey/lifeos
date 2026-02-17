<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
            'is_admin' => 'boolean',
        ];
    }

    public function habits(): HasMany
    {
        return $this->hasMany(\App\Modules\Habits\Models\Habit::class);
    }

    public function boards(): HasMany
    {
        return $this->hasMany(\App\Modules\Tasks\Models\Board::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(\App\Modules\Finance\Models\Transaction::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(\App\Modules\Finance\Models\Category::class, 'user_id');
    }

    public function modules()
    {
        return $this->hasMany(\App\Modules\Admin\Models\UserModule::class);
    }

    public function hasModule(string $module): bool
    {
        return $this->modules()->where('module', $module)->where('is_active', true)->exists();
    }

    public function getModule(string $slug): ?\App\Modules\Admin\Models\UserModule
    {
        return $this->modules()->where('module', $slug)->where('is_active', true)->first();
    }

    public function getModuleLimit(string $slug, string $key): ?int
    {
        $module = $this->getModule($slug);

        return $module?->getLimit($key);
    }
}
