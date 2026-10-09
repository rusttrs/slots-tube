<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserAdminCommand extends Command
{
    protected $signature = 'user:admin {email? : Почта пользователя; без неё — список админов} {--revoke : Забрать доступ в админку}';

    protected $description = 'Выдаёт или забирает доступ в админку /admin';

    public function handle(): int
    {
        $email = $this->argument('email');

        if ($email === null) {
            $admins = User::query()->where('is_admin', true)->orderBy('id')->get(['id', 'email', 'name', 'deactivated_at']);
            $this->table(['ID', 'Почта', 'Имя', 'Активен'], $admins->map(fn (User $user) => [
                $user->id, $user->email, $user->name, $user->isActive() ? 'да' : 'нет',
            ]));

            return self::SUCCESS;
        }

        $user = User::query()->whereRaw('lower(email) = ?', [mb_strtolower(trim($email))])->first();
        if (! $user) {
            $this->error("Пользователь {$email} не найден.");

            return self::FAILURE;
        }

        $grant = ! $this->option('revoke');
        $user->forceFill(['is_admin' => $grant])->save();

        $this->info($grant ? "{$user->email}: доступ в админку выдан." : "{$user->email}: доступ в админку забран.");
        if ($grant && blank($user->password)) {
            $this->warn('У пользователя нет пароля — задайте его, чтобы войти через /admin/login.');
        }

        return self::SUCCESS;
    }
}
