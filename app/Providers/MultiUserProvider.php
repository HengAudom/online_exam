<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Student;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

class MultiUserProvider implements UserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        if (is_null($identifier)) {
            return null;
        }

        if (is_string($identifier)) {
            if (str_starts_with($identifier, 'student:')) {
                return Student::find(substr($identifier, 8));
            }
            if (str_starts_with($identifier, 'admin:')) {
                return Admin::find(substr($identifier, 6));
            }
        }

        // Fallback for legacy numeric IDs: try Admin then Student
        $admin = Admin::find($identifier);
        if ($admin) {
            return $admin;
        }

        return Student::find($identifier);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed   $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        $user = $this->retrieveById($identifier);

        if (!$user) {
            return null;
        }

        $rememberToken = $user->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $user : null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $user->save();
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) ||
           (count($credentials) === 1 &&
            array_key_exists('password', $credentials))) {
            return null;
        }

        $identifier = $credentials['username'] ?? $credentials['identifier'] ?? $credentials['email'] ?? null;

        if (!$identifier) {
            return null;
        }

        // 1. Try finding Admin by Username
        $admin = Admin::whereRaw('LOWER(Username) = ?', [strtolower($identifier)])->first();
        if ($admin) {
            return $admin;
        }

        // 2. Try finding Student by StudentCode or StudentId
        return Student::where(function ($q) use ($identifier) {
            $q->whereRaw('LOWER(StudentCode) = ?', [strtolower($identifier)]);
            if (is_numeric($identifier)) {
                $q->orWhere('StudentId', (int)$identifier);
            }
        })->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'] ?? '';

        // If user is a Student, password is not required
        if ($user instanceof Student) {
            return true;
        }

        return Hash::check($plain, $user->getAuthPassword());
    }

    /**
     * Rehash the user's password if required and supported.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @param  bool  $force
     * @return void
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        if ($user instanceof Admin && !empty($credentials['password'])) {
            if ($force || Hash::needsRehash($user->getAuthPassword())) {
                $user->Password = Hash::make($credentials['password']);
                $user->save();
            }
        }
    }
}
