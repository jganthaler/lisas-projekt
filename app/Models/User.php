<?php

namespace App\Models;

use App\Support\Locales;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The account of the app. The e-mail address has to be confirmed before the
 * freezer can be used - the signed link of the verification mail is handled by
 * App\Http\Controllers\VerifyEmailController.
 *
 * The language the user picked in the profile lives on the account as well, so
 * that pages and mails can be written in it (see App\Support\Locales).
 */
#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    /**
     * The language this user picked in the profile. An account without a valid
     * choice - a guest, a fresh registration, an old row - gets the default of
     * the app.
     */
    public function localeOrDefault(): string
    {
        $locale = (string) $this->locale;

        return Locales::supports($locale) ? $locale : Locales::default();
    }

    /**
     * The language Laravel uses for the notifications of this user: the
     * NotificationSender renders a notification inside the locale of its
     * recipient and asks for it through this interface, so the verification
     * and reset mails are written in the language of the account.
     */
    public function preferredLocale(): string
    {
        return $this->localeOrDefault();
    }

    /**
     * Freezers this user created (and therefore owns).
     *
     * @return HasMany<Freezer, $this>
     */
    public function ownedFreezers(): HasMany
    {
        return $this->hasMany(Freezer::class, 'owner_id');
    }

    /**
     * Memberships of this user - the owner membership is stored here as well.
     *
     * @return HasMany<FreezerMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(FreezerMembership::class);
    }

    /**
     * Every freezer this user may access, no matter whether it is owned or shared.
     *
     * @return BelongsToMany<Freezer, $this>
     */
    public function freezers(): BelongsToMany
    {
        return $this->belongsToMany(Freezer::class, 'freezer_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Items that need attention in every freezer this user may access - own and
     * shared ones. This is the number on the MHD field of the bottom navigation
     * and it matches the entries of the MHD watch list, because it counts the
     * very same set: a best before date up to the end of the warning window.
     */
    public function warningItemCount(): int
    {
        return InventoryItem::query()
            ->whereHas('drawer', fn (Builder $query): Builder => $query->whereIn(
                'freezer_id',
                $this->freezers()->select('freezers.id'),
            ))
            ->withBestBefore()
            ->whereDate('best_before', '<=', Carbon::today()->addDays(InventoryItem::WARNING_DAYS)->toDateString())
            ->count();
    }

    /**
     * Invitations this user handed out to other people.
     *
     * @return HasMany<Invitation, $this>
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'inviter_id');
    }

    /**
     * Determine whether this user is allowed to work in the given freezer.
     */
    public function isMemberOf(Freezer $freezer): bool
    {
        return $this->memberships()
            ->where('freezer_id', $freezer->getKey())
            ->exists();
    }

    /**
     * Role of this user inside the given freezer ("owner", "member") or null.
     */
    public function roleIn(Freezer $freezer): ?string
    {
        $role = $this->memberships()
            ->where('freezer_id', $freezer->getKey())
            ->value('role');

        return $role === null ? null : (string) $role;
    }

    /**
     * Name shown inside the app - falls back to the local part of the email.
     */
    public function displayName(): string
    {
        $name = trim((string) $this->name);

        return $name !== '' ? $name : (string) Str::before((string) $this->email, '@');
    }

    /**
     * Short placeholder for the avatar circle ("LM" for "Lisa Muster").
     */
    public function initials(): string
    {
        $initials = '';

        foreach (array_slice(preg_split('/\s+/u', $this->displayName()) ?: [], 0, 2) as $part) {
            $initials .= Str::upper(Str::substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : '?';
    }

    public function hasAvatar(): bool
    {
        return filled($this->avatar_path);
    }

    /**
     * URL of the uploaded profile picture, null when none was uploaded yet.
     *
     * The version in the query is derived from the stored path, which changes
     * with every upload: without it the browser would keep showing the old
     * picture, because /avatars/{user} is cached for an hour.
     */
    public function avatarUrl(): ?string
    {
        if (! $this->hasAvatar()) {
            return null;
        }

        return route('avatars.show', [
            'user' => $this,
            'v' => Str::substr(md5((string) $this->avatar_path), 0, 8),
        ]);
    }
}
