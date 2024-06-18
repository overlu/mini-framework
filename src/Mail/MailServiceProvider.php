<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail;

use Mini\Contracts\Support\DeferrableProvider;
use Mini\Service\AbstractServiceProvider;

class MailServiceProvider extends AbstractServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerMiniMailer();
        $this->registerMarkdownRenderer();
    }

    /**
     * Register the Mini mailer instance.
     *
     * @return void
     */
    protected function registerMiniMailer(): void
    {
        $this->app->singleton('mail.manager', function ($app) {
            return new MailManager($app);
        });

        $this->app->bind('mailer', function ($app) {
            return $app->make('mail.manager')->mailer();
        });
    }

    /**
     * Register the Markdown renderer instance.
     *
     * @return void
     */
    protected function registerMarkdownRenderer(): void
    {
        if (RUN_ENV === 'artisan') {
            $this->publishes([
                __DIR__ . '/resources/views' => resource_path('views/mail'),
            ], 'laravel-mail');
        }

        $this->app->singleton(Markdown::class, function ($app) {
            $config = $app->make('config');

            return new Markdown($app->make('view'), [
                'theme' => $config->get('mail.markdown.theme', 'default'),
                'paths' => $config->get('mail.markdown.paths', []),
            ]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'mail.manager',
            'mailer',
            Markdown::class,
        ];
    }


    /**
     * @return void
     */
    public function boot(): void
    {
        // TODO: Implement boot() method.
    }
}
