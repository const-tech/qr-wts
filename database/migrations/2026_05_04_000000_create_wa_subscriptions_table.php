<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWaSubscriptionsTable extends Migration
{
    public function up(): void
    {
        $table = config('whatsapp-gateway.storage.table', 'wa_subscriptions');
        $connection = config('whatsapp-gateway.storage.connection');

        $schema = $connection ? Schema::connection($connection) : Schema::getFacadeRoot();

        if ($schema->hasTable($table)) {
            return;
        }

        $schema->create($table, function (Blueprint $t) {
            $t->id();
            $t->uuid('local_token')->unique();
            $t->string('name');
            $t->string('phone', 32);
            $t->string('email')->nullable();
            $t->string('business')->nullable();
            $t->string('package_id')->default('free');
            $t->string('instance_id')->index();
            $t->string('token')->nullable();
            $t->string('remote_id')->nullable();
            $t->string('status', 32)->default('pending');
            $t->timestamp('expires_at')->nullable();
            $t->string('dashboard_url')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        $table = config('whatsapp-gateway.storage.table', 'wa_subscriptions');
        $connection = config('whatsapp-gateway.storage.connection');

        $schema = $connection ? Schema::connection($connection) : Schema::getFacadeRoot();
        $schema->dropIfExists($table);
    }
}
