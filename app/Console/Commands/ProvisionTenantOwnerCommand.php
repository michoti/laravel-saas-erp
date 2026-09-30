<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Tenants\ProvisionTenantOwnerUser;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Throwable;

/**
 * Manual retry path for CreateTenant's owner-provisioning step, for the
 * case where the tenant database wasn't ready yet the first time (see
 * ProvisionTenantOwnerUser's docblock).
 *
 * Usage: php artisan tenants:provision-owner {tenant} {email}
 */
final class ProvisionTenantOwnerCommand extends Command
{
    protected $signature = 'tenants:provision-owner {tenant : Tenant ID} {email : Owner email address}';

    protected $description = "Create the Owner user in a tenant's own database (retry for a failed CreateTenant provisioning step)";

    public function handle(ProvisionTenantOwnerUser $provisioner): int
    {
        $tenant = Tenant::find($this->argument('tenant'));

        if (! $tenant instanceof Tenant) {
            $this->error("No tenant found with id [{$this->argument('tenant')}].");

            return self::FAILURE;
        }

        try {
            $credentials = $provisioner->handle($tenant, $this->argument('email'));
        } catch (Throwable $e) {
            $this->error("Could not provision the owner user: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info('Owner account created.');
        $this->line("Email: {$credentials['email']}");
        $this->line("Temporary password: {$credentials['password']}");

        return self::SUCCESS;
    }
}
