<?php

namespace TypiCMS\Modules\Core\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use TypiCMS\Modules\Users\Shells\Repositories\UserInterface;

class Install extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'admintool:install';

    /**
     * The console command description.
     *
     * @var string
     */

    protected $description = 'Installation of Admintool4: Laravel setup, installation of composer and npm packages';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The user model.
     *
     * @var \TypiCMS\Modules\Users\Shells\Repositories\UserInterface
     */
    protected $user;

    /**
     * Create a new command.
     *
     * @param \TypiCMS\Modules\Users\Shells\Repositories\UserInterface $user
     * @param \Illuminate\Filesystem\Filesystem                 $files
     */
    public function __construct(UserInterface $user, Filesystem $files)
    {
        parent::__construct();

        $this->user = $user;
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->line('------------------');
        $this->line('Welcome to Admintool4');
        $this->line('------------------');

        $this->info('Publishing vendor packages...');
        $this->call('vendor:publish');
        $this->line('------------------');

        $this->laravel['env'] = 'local';

        // Ask for database name
        $this->info('Setting up database...');
        $dbName = $this->ask('Enter a database name', $this->guessDatabaseName());

        // Set database credentials in .env and migrate
        $this->call('typicms:database', ['database' => $dbName]);
        $this->line('------------------');

        // Create a super user
        $this->createSuperUser();

        // Set cache key prefix
        $this->call('cache:prefix', ['prefix' => $dbName]);
        $this->line('------------------');

        // Composer install
        if (function_exists('system')) {
            system('find storage -type d -exec chmod 755 {} \;');
            $this->info('Directory storage is now writable (755).');
            system('find bootstrap/cache -type d -exec chmod 755 {} \;');
            $this->info('Directory bootstrap/cache is now writable (755).');
            system('find public/uploads -type d -exec chmod 755 {} \;');
            $this->info('Directory public/uploads is now writable (755).');
            system('find public/html -type d -exec chmod 755 {} \;');
            $this->info('Directory public/html is now writable (755).');
            $this->line('------------------');
            $this->info('Running npm install...');
            system('npm install');
            $this->info('npm packages installed.');
        } else {
            $this->line('You can now make /storage, /bootstrap/cache and /public/uploads directories writable.');
            $this->line('and run composer install and npm install.');
        }

        // Done
        $this->line('------------------');
        $this->line('Done. Enjoy Admintool4!');
    }

    /**
     * Guess database name from app folder.
     *
     * @return string
     */
    public function guessDatabaseName()
    {
        try {
            $segments = array_reverse(explode(DIRECTORY_SEPARATOR, app_path()));
            $name = explode('.', $segments[1])[0];

            return str_slug($name);
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Create a superuser.
     */
    private function createSuperUser()
    {
        $this->info('Creating a Super User...');

        $firstname = $this->ask('Enter your first name');
        $lastname = $this->ask('Enter your last name');
        $email = $this->ask('Enter your email address');
        $password = $this->secret('Enter a password');

        $data = [
            'first_name'  => $firstname,
            'last_name'   => $lastname,
            'email'       => $email,
            'superuser'   => 1,
            'activated'   => 1,
            'password'    => $password,
        ];

        try {
            $this->user->create($data);
            $this->info('Superuser created.');
        } catch (Exception $e) {
            $this->error('User could not be created.');
        }

        $this->line('------------------');
    }
}
