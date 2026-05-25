<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * @var string
     */
    protected $signature = 'admin:create
        {--name= : Nombre del usuario}
        {--email= : Email del usuario}
        {--password= : Contraseña (si se omite se pedirá de forma oculta)}
        {--no-admin : Crear el usuario SIN privilegios de administrador}';

    /**
     * @var string
     */
    protected $description = 'Crea (o actualiza) un usuario administrador de LifeOS';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nombre');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Contraseña');
        $isAdmin = ! $this->option('no-admin');

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $existing = User::where('email', $email)->exists();

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_admin' => $isAdmin,
            ]
        );

        $this->info(sprintf(
            '%s %s (%s)%s',
            $existing ? 'Usuario actualizado:' : 'Usuario creado:',
            $user->name,
            $user->email,
            $isAdmin ? ' [admin]' : ''
        ));

        return self::SUCCESS;
    }
}
