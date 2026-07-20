<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->boolean('is_subscribed')
                ->default(false)
                ->after('mbti_type');

            $table->string('subscription_plan', 30)
                ->nullable()
                ->after('is_subscribed');

            $table->decimal('subscription_price', 10, 2)
                ->nullable()
                ->after('subscription_plan');

            $table->timestamp('subscribed_at')
                ->nullable()
                ->after('subscription_price');

            $table->timestamp('subscription_expires_at')
                ->nullable()
                ->after('subscribed_at');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'is_subscribed',
                'subscription_plan',
                'subscription_price',
                'subscribed_at',
                'subscription_expires_at',
            ]);
        });
    }
};