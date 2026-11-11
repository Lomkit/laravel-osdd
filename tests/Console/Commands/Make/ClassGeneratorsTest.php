<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ClassGeneratorsTest extends TestCase
{
    private const string LAYER = 'functional/test-layer';

    /**
     * @return array<string, array{GeneratorCase}>
     */
    public static function generators(): array
    {
        return [
            'cast' => [new GeneratorCase('osdd:cast', 'MoneyAmount', 'src/Casts/MoneyAmount.php', 'Functional\TestLayer\Casts\MoneyAmount')],
            'channel' => [new GeneratorCase('osdd:channel', 'OrderChannel', 'src/Broadcasting/OrderChannel.php', 'Functional\TestLayer\Broadcasting\OrderChannel')],
            'class' => [new GeneratorCase('osdd:class', 'PaymentService', 'src/PaymentService.php', 'Functional\TestLayer\PaymentService')],
            'command' => [new GeneratorCase('osdd:command', 'SendReportCommand', 'src/Console/Commands/SendReportCommand.php', 'Functional\TestLayer\Console\Commands\SendReportCommand')],
            'component' => [new GeneratorCase('osdd:component', 'StatusBadge', 'src/View/Components/StatusBadge.php', 'Functional\TestLayer\View\Components\StatusBadge')],
            'controller' => [new GeneratorCase('osdd:controller', 'UserController', 'src/Http/Controllers/UserController.php', 'Functional\TestLayer\Http\Controllers\UserController')],
            'enum' => [new GeneratorCase('osdd:enum', 'OrderStatus', 'src/Enums/OrderStatus.php', 'Functional\TestLayer\Enums\OrderStatus')],
            'event' => [new GeneratorCase('osdd:event', 'OrderPlaced', 'src/Events/OrderPlaced.php', 'Functional\TestLayer\Events\OrderPlaced')],
            'exception' => [new GeneratorCase('osdd:exception', 'OrderNotFoundException', 'src/Exceptions/OrderNotFoundException.php', 'Functional\TestLayer\Exceptions\OrderNotFoundException')],
            'factory' => [new GeneratorCase('osdd:factory', 'UserFactory', 'database/factories/UserFactory.php', 'Functional\TestLayer\Database\Factories\UserFactory')],
            'interface' => [new GeneratorCase('osdd:interface', 'PaymentGateway', 'src/Contracts/PaymentGateway.php', 'Functional\TestLayer\Contracts\PaymentGateway')],
            'job' => [new GeneratorCase('osdd:job', 'ProcessPayment', 'src/Jobs/ProcessPayment.php', 'Functional\TestLayer\Jobs\ProcessPayment')],
            'job middleware' => [new GeneratorCase('osdd:job-middleware', 'RateLimitedOrders', 'src/Jobs/Middleware/RateLimitedOrders.php', 'Functional\TestLayer\Jobs\Middleware\RateLimitedOrders')],
            'listener' => [new GeneratorCase('osdd:listener', 'SendOrderConfirmation', 'src/Listeners/SendOrderConfirmation.php', 'Functional\TestLayer\Listeners\SendOrderConfirmation')],
            'mail' => [new GeneratorCase('osdd:mail', 'WelcomeMail', 'src/Mail/WelcomeMail.php', 'Functional\TestLayer\Mail\WelcomeMail')],
            'middleware' => [new GeneratorCase('osdd:middleware', 'Authenticate', 'src/Http/Middleware/Authenticate.php', 'Functional\TestLayer\Http\Middleware\Authenticate')],
            'model' => [new GeneratorCase('osdd:model', 'Invoice', 'src/Models/Invoice.php', 'Functional\TestLayer\Models\Invoice')],
            'notification' => [new GeneratorCase('osdd:notification', 'WelcomeNotification', 'src/Notifications/WelcomeNotification.php', 'Functional\TestLayer\Notifications\WelcomeNotification')],
            'observer' => [new GeneratorCase('osdd:observer', 'UserObserver', 'src/Observers/UserObserver.php', 'Functional\TestLayer\Observers\UserObserver')],
            'policy' => [new GeneratorCase('osdd:policy', 'InvoicePolicy', 'src/Policies/InvoicePolicy.php', 'Functional\TestLayer\Policies\InvoicePolicy')],
            'provider' => [new GeneratorCase('osdd:provider', 'BillingServiceProvider', 'src/Providers/BillingServiceProvider.php', 'Functional\TestLayer\Providers\BillingServiceProvider')],
            'request' => [new GeneratorCase('osdd:request', 'StoreUserRequest', 'src/Http/Requests/StoreUserRequest.php', 'Functional\TestLayer\Http\Requests\StoreUserRequest')],
            'resource' => [new GeneratorCase('osdd:resource', 'UserResource', 'src/Http/Resources/UserResource.php', 'Functional\TestLayer\Http\Resources\UserResource')],
            'rule' => [new GeneratorCase('osdd:rule', 'Uppercase', 'src/Rules/Uppercase.php', 'Functional\TestLayer\Rules\Uppercase')],
            'scope' => [new GeneratorCase('osdd:scope', 'ActiveScope', 'src/Models/Scopes/ActiveScope.php', 'Functional\TestLayer\Models\Scopes\ActiveScope')],
            'seeder' => [new GeneratorCase('osdd:seeder', 'UserSeeder', 'database/seeders/UserSeeder.php', 'Functional\TestLayer\Database\Seeders\UserSeeder')],
            'unit test' => [new GeneratorCase('osdd:test', 'UserTest', 'tests/Unit/UserTest.php', 'Functional\TestLayer\Tests\Unit\UserTest', ['--unit' => true])],
            'trait' => [new GeneratorCase('osdd:trait', 'HasTimestamps', 'src/Concerns/HasTimestamps.php', 'Functional\TestLayer\Concerns\HasTimestamps')],
        ];
    }

    /**
     * @return array<string, array{GeneratorCase}>
     */
    public static function nestedNames(): array
    {
        return [
            'nested class' => [new GeneratorCase('osdd:class', 'Services/RefundService', 'src/Services/RefundService.php', 'Functional\TestLayer\Services\RefundService')],
            'nested controller' => [new GeneratorCase('osdd:controller', 'Admin/AdminController', 'src/Http/Controllers/Admin/AdminController.php', 'Functional\TestLayer\Http\Controllers\Admin\AdminController')],
            'nested event' => [new GeneratorCase('osdd:event', 'Auth/SessionOpened', 'src/Events/Auth/SessionOpened.php', 'Functional\TestLayer\Events\Auth\SessionOpened')],
            'nested exception' => [new GeneratorCase('osdd:exception', 'Auth/ExpiredSessionException', 'src/Exceptions/Auth/ExpiredSessionException.php', 'Functional\TestLayer\Exceptions\Auth\ExpiredSessionException')],
            'nested factory' => [new GeneratorCase('osdd:factory', 'Admin/AdminFactory', 'database/factories/Admin/AdminFactory.php', 'Functional\TestLayer\Database\Factories\Admin\AdminFactory')],
            'nested listener' => [new GeneratorCase('osdd:listener', 'Auth/RecordSessionStart', 'src/Listeners/Auth/RecordSessionStart.php', 'Functional\TestLayer\Listeners\Auth\RecordSessionStart')],
            'nested model' => [new GeneratorCase('osdd:model', 'Admin/Operator', 'src/Models/Admin/Operator.php', 'Functional\TestLayer\Models\Admin\Operator')],
            'nested notification' => [new GeneratorCase('osdd:notification', 'Auth/PasswordResetNotification', 'src/Notifications/Auth/PasswordResetNotification.php', 'Functional\TestLayer\Notifications\Auth\PasswordResetNotification')],
            'nested policy' => [new GeneratorCase('osdd:policy', 'Admin/AdminPolicy', 'src/Policies/Admin/AdminPolicy.php', 'Functional\TestLayer\Policies\Admin\AdminPolicy')],
            'nested provider' => [new GeneratorCase('osdd:provider', 'Admin/AdminServiceProvider', 'src/Providers/Admin/AdminServiceProvider.php', 'Functional\TestLayer\Providers\Admin\AdminServiceProvider')],
            'nested request' => [new GeneratorCase('osdd:request', 'Admin/StoreAdminRequest', 'src/Http/Requests/Admin/StoreAdminRequest.php', 'Functional\TestLayer\Http\Requests\Admin\StoreAdminRequest')],
            'nested seeder' => [new GeneratorCase('osdd:seeder', 'Admin/AdminSeeder', 'database/seeders/Admin/AdminSeeder.php', 'Functional\TestLayer\Database\Seeders\Admin\AdminSeeder')],
            'nested unit test' => [new GeneratorCase('osdd:test', 'Services/UserServiceTest', 'tests/Unit/Services/UserServiceTest.php', 'Functional\TestLayer\Tests\Unit\Services\UserServiceTest', ['--unit' => true])],
        ];
    }

    #[DataProvider('generators')]
    #[DataProvider('nestedNames')]
    public function test_the_class_is_written_in_the_layer_and_loads(GeneratorCase $case): void
    {
        $this->artisan($case->command, [...$case->arguments(), '--layer' => self::LAYER])->assertExitCode(0);

        $generated = GeneratedClass::load($this->layerPath($case->file), $case->class);

        $this->assertSame($case->class, $generated->getName());
        $this->assertSame($this->layerPath($case->file), $generated->getFileName());
        $this->assertDirectoryDoesNotExist($this->app->path());
    }

    #[DataProvider('generators')]
    public function test_it_asks_for_the_layer_when_none_is_given(GeneratorCase $case): void
    {
        $prompted = $case->prefixed('Prompted');

        $this->artisan($prompted->command, $prompted->arguments())
            ->expectsSearch('Which layer should this be generated in?', self::LAYER, '', [self::LAYER => self::LAYER])
            ->assertExitCode(0);

        $this->assertFileExists($this->layerPath($prompted->file));
    }

    private function layerPath(string $file): string
    {
        return $this->project()->path(self::LAYER.'/'.$file);
    }
}
