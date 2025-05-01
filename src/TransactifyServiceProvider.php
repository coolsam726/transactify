<?php

namespace Coolsam\Transactify;

use Coolsam\Transactify\Commands\TransactifyCommand;
use Coolsam\Transactify\Support\Utils;
use Illuminate\Console\Command;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

use function Laravel\Prompts\confirm;

class TransactifyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('transactify')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_transactify_tables')
            ->hasInstallCommand(function (InstallCommand $install) {
                $install
                    ->startWith(function (Command $command) {
                        if (confirm(__('Do you want to publish and overwrite the config file? (The existing file will be backed up to .bak)'))) {
                            // check if the config file exists and back it up by copying to .bak
                            if (file_exists(config_path('transactify.php'))) {
                                $command->info('Backing up existing config to .bak file');
                                // copy the config file to .bak
                                copy(config_path('transactify.php'), config_path('transactify.php.bak'));
                            }
                            $command->call('vendor:publish', [
                                '--tag' => 'transactify-config',
                                '--force' => true,
                            ]);
                        }

                        if (confirm(__('Do you want to publish the migrations?'))) {
                            $command->call('vendor:publish', [
                                '--tag' => 'transactify-migrations',
                                '--force' => true,
                            ]);
                        }
                    })
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('coolsam726/transactify');
            })
            ->hasCommand(TransactifyCommand::class);
    }

    public function packageBooted(): void
    {
        $this->app->singleton(Transactify::class, function ($app) {
            $instance = new Transactify();
            return $instance->init();
        });

        $this->app->alias(Transactify::class, 'transactify');

        $this->app->singleton('transactify.utils', function ($app) {
            return new Utils();
        });
        $this->app->alias(Utils::class, 'transactify.utils');
    }
}
